import {
    incidents as syncIncidents,
    panic as syncPanic,
    patrols as syncPatrols,
    visits as syncVisits,
} from '../routes/guard/sync';
import {
    deleteOutboxItem,
    getKv,
    outboxItems,
    setKv,
    updateOutboxItem,
} from './db';
import type { OutboxItem, OutboxType, StoredPhoto } from './types';

export const OUTBOX_SYNC_TAG = 'rdi-outbox';

export const SYNC_CHANNEL = 'rdi-guard-sync';

export const BRANDING_CACHE = 'rdi-branding-v2';

const MAX_BACKOFF_MS = 15 * 60 * 1000;

export type SyncResult = {
    synced: number;
    failed: number;
    pending: number;
    authRequired: boolean;
    offline: boolean;
};

const endpoints: Record<OutboxType, () => string> = {
    patrol: () => syncPatrols.url(),
    visit: () => syncVisits.url(),
    incident: () => syncIncidents.url(),
    panic: () => syncPanic.url(),
};

let running: Promise<SyncResult> | null = null;

/**
 * Uploads pending outbox items in creation order. Safe to call from the page
 * and from the service worker; concurrent runs are serialized.
 */
export function syncOutbox(): Promise<SyncResult> {
    running ??= withLock(processOutbox).finally(() => {
        running = null;
    });

    return running;
}

async function withLock(task: () => Promise<SyncResult>): Promise<SyncResult> {
    const locks = (globalThis.navigator as Navigator | undefined)?.locks;

    if (!locks) {
        return task();
    }

    return locks.request('rdi-guard-sync', task);
}

async function processOutbox(): Promise<SyncResult> {
    const result: SyncResult = {
        synced: 0,
        failed: 0,
        pending: 0,
        authRequired: false,
        offline: false,
    };

    const csrfToken = await getKv('csrf_token');
    const items = (await outboxItems()).sort(
        (a, b) =>
            Number(b.type === 'panic') - Number(a.type === 'panic') ||
            (a.id ?? 0) - (b.id ?? 0),
    );

    for (const item of items) {
        if (item.status === 'failed') {
            result.failed++;

            continue;
        }

        if (
            result.offline ||
            result.authRequired ||
            item.next_attempt_at > Date.now()
        ) {
            result.pending++;

            continue;
        }

        const outcome = await send(item, csrfToken);

        switch (outcome.kind) {
            case 'synced':
                await deleteOutboxItem(item.id!);
                result.synced++;
                break;
            case 'rejected':
                await updateOutboxItem({
                    ...item,
                    status: 'failed',
                    attempts: item.attempts + 1,
                    last_error: outcome.message,
                });
                result.failed++;
                break;
            case 'auth':
                await setKv('auth_required', true);
                result.authRequired = true;
                result.pending++;
                break;
            case 'retry':
            case 'offline':
                await updateOutboxItem({
                    ...item,
                    attempts: item.attempts + 1,
                    next_attempt_at: Date.now() + backoff(item.attempts + 1),
                    last_error: outcome.message,
                });
                result.offline = outcome.kind === 'offline';
                result.pending++;
                break;
        }
    }

    if (result.synced > 0) {
        await setKv('last_sync_at', new Date().toISOString());
    }

    if (!result.authRequired && result.synced > 0) {
        await setKv('auth_required', false);
    }

    broadcast(result);

    return result;
}

type SendOutcome =
    | { kind: 'synced' }
    | { kind: 'rejected'; message: string }
    | { kind: 'auth' }
    | { kind: 'retry'; message: string }
    | { kind: 'offline'; message: string };

async function send(
    item: OutboxItem,
    csrfToken: string | undefined,
): Promise<SendOutcome> {
    let response: Response;
    let body: FormData;

    try {
        body = await buildBody(item);
    } catch {
        return {
            kind: 'rejected',
            message: 'No se pudieron leer las fotos guardadas.',
        };
    }

    try {
        response = await fetch(endpoints[item.type](), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body,
        });
    } catch {
        return { kind: 'offline', message: 'Sin conexión' };
    }

    if (response.ok) {
        return { kind: 'synced' };
    }

    if (
        response.status === 401 ||
        response.status === 419 ||
        response.redirected
    ) {
        return { kind: 'auth' };
    }

    if (
        response.status === 422 ||
        response.status === 403 ||
        response.status === 404
    ) {
        return { kind: 'rejected', message: await errorMessage(response) };
    }

    return { kind: 'retry', message: await errorMessage(response) };
}

async function buildBody(item: OutboxItem): Promise<FormData> {
    const form = new FormData();

    appendValue(form, '', item.payload);

    const photos = await Promise.all(item.photos.map(toUploadBlob));

    photos.forEach((photo, index) => {
        form.append('photos[]', photo, `foto-${index + 1}.jpg`);
    });

    return form;
}

async function toUploadBlob(photo: StoredPhoto | Blob): Promise<Blob> {
    if (photo instanceof Blob) {
        return new Blob([await photo.arrayBuffer()], {
            type: photo.type || 'image/jpeg',
        });
    }

    return new Blob([photo.buffer], { type: photo.type });
}

function appendValue(form: FormData, key: string, value: unknown): void {
    if (value === null || value === undefined) {
        return;
    }

    if (typeof value === 'object' && !(value instanceof Blob)) {
        Object.entries(value as Record<string, unknown>).forEach(
            ([childKey, childValue]) => {
                appendValue(
                    form,
                    key === '' ? childKey : `${key}[${childKey}]`,
                    childValue,
                );
            },
        );

        return;
    }

    if (typeof value === 'boolean') {
        form.append(key, value ? '1' : '0');

        return;
    }

    form.append(key, value instanceof Blob ? value : String(value));
}

async function errorMessage(response: Response): Promise<string> {
    try {
        const body = (await response.json()) as {
            message?: string;
            errors?: Record<string, string[]>;
        };
        const firstError = body.errors
            ? Object.values(body.errors)[0]?.[0]
            : undefined;

        return firstError ?? body.message ?? `Error ${response.status}`;
    } catch {
        return `Error ${response.status}`;
    }
}

function backoff(attempts: number): number {
    return Math.min(MAX_BACKOFF_MS, 2 ** attempts * 5000);
}

function broadcast(result: SyncResult): void {
    if (typeof BroadcastChannel === 'undefined') {
        return;
    }

    const channel = new BroadcastChannel(SYNC_CHANNEL);
    channel.postMessage(result);
    channel.close();
}

/**
 * Lets failed items be retried, e.g. after the guard signs in again.
 */
export async function retryFailedItems(): Promise<void> {
    for (const item of await outboxItems()) {
        if (item.status === 'failed' || item.next_attempt_at > 0) {
            await updateOutboxItem({
                ...item,
                status: 'pending',
                next_attempt_at: 0,
            });
        }
    }
}

export async function requestBackgroundSync(): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    try {
        const registration = (await navigator.serviceWorker
            .ready) as ServiceWorkerRegistration & {
            sync?: { register: (tag: string) => Promise<void> };
        };

        await registration.sync?.register(OUTBOX_SYNC_TAG);
    } catch {
        // Background Sync is not available (e.g. iOS); foreground sync covers it.
    }
}
