import { packageMethod as offlinePackage } from '../routes/guard/offline';
import { enqueue, getKv, patrolsForUser, savePatrol, setKv } from './db';
import { requestBackgroundSync, syncOutbox } from './sync';
import type {
    LocalPatrol,
    OfflineCheckpoint,
    OfflinePackage,
    OfflineRound,
} from './types';

export const DATA_CHANGED_EVENT = 'rdi-guard-data-changed';

export type PackageRefreshResult = 'updated' | 'offline' | 'auth';

export function notifyDataChanged(): void {
    window.dispatchEvent(new Event(DATA_CHANGED_EVENT));
}

export async function refreshPackage(): Promise<PackageRefreshResult> {
    let response: Response;

    try {
        response = await fetch(offlinePackage.url(), {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    } catch {
        return 'offline';
    }

    if (
        response.status === 401 ||
        response.status === 419 ||
        response.redirected
    ) {
        await setKv('auth_required', true);
        notifyDataChanged();

        return 'auth';
    }

    if (!response.ok) {
        return 'offline';
    }

    const data = (await response.json()) as OfflinePackage;
    const previous = await getKv('package');

    if (previous && previous.user.id !== data.user.id) {
        await setKv('selected_area_id', data.areas[0]?.id ?? 0);
    }

    await setKv('package', data);
    await setKv('csrf_token', data.csrf_token);
    await setKv('auth_required', false);
    await adoptServerPatrol(data);

    notifyDataChanged();

    return 'updated';
}

/**
 * Patrols started from the online flow are adopted so they can be finished offline.
 */
async function adoptServerPatrol(data: OfflinePackage): Promise<void> {
    if (!data.active_patrol) {
        return;
    }

    const patrols = await patrolsForUser(data.user.id);

    if (
        patrols.some(
            (patrol) => patrol.client_uuid === data.active_patrol?.client_uuid,
        )
    ) {
        return;
    }

    if (patrols.some((patrol) => patrol.finished_at === null)) {
        return;
    }

    await savePatrol({
        client_uuid: data.active_patrol.client_uuid,
        user_id: data.user.id,
        round_id: data.active_patrol.round_id,
        started_at: data.active_patrol.started_at,
        finished_at: null,
        visits: data.active_patrol.visited_checkpoint_ids.map(
            (checkpointId) => ({
                checkpoint_id: checkpointId,
                outcome: 'all_clear',
                reviewed_at: data.active_patrol!.started_at,
                is_urgent: false,
            }),
        ),
    });
}

export async function syncNow(): Promise<void> {
    await syncOutbox();
    notifyDataChanged();
}

async function afterChange(): Promise<void> {
    notifyDataChanged();
    await requestBackgroundSync();

    if (navigator.onLine) {
        void syncNow();
    }
}

export function newUuid(): string {
    if (typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = crypto.getRandomValues(new Uint8Array(1))[0] % 16;
        const value = char === 'x' ? random : (random & 0x3) | 0x8;

        return value.toString(16);
    });
}

export async function startPatrol(
    userId: number,
    round: OfflineRound,
): Promise<LocalPatrol> {
    const patrols = await patrolsForUser(userId);
    const active = patrols.find((patrol) => patrol.finished_at === null);

    if (active) {
        if (active.round_id === round.id) {
            return active;
        }

        throw new Error(
            'Ya tienes otro recorrido en curso. Termínalo antes de iniciar uno nuevo.',
        );
    }

    const patrol: LocalPatrol = {
        client_uuid: newUuid(),
        user_id: userId,
        round_id: round.id,
        started_at: new Date().toISOString(),
        finished_at: null,
        visits: [],
    };

    await savePatrol(patrol);
    await enqueue({
        type: 'patrol',
        user_id: userId,
        label: `Inicio de recorrido: ${round.title}`,
        payload: {
            client_uuid: patrol.client_uuid,
            round_id: round.id,
            started_at: patrol.started_at,
        },
        photos: [],
    });
    await afterChange();

    return patrol;
}

type VisitInput = {
    patrol: LocalPatrol;
    round: OfflineRound;
    checkpoint: OfflineCheckpoint;
    isUrgent: boolean;
    urgentNotes: string;
    photos: File[];
};

export async function recordQuestionnaire(
    input: VisitInput & { answers: Record<number, number> },
): Promise<LocalPatrol> {
    return recordVisit(input, 'questionnaire', { answers: input.answers });
}

export async function recordAllClear(input: VisitInput): Promise<LocalPatrol> {
    return recordVisit(input, 'all_clear', {});
}

async function recordVisit(
    input: VisitInput,
    outcome: 'questionnaire' | 'all_clear',
    extra: Record<string, unknown>,
): Promise<LocalPatrol> {
    const reviewedAt = new Date().toISOString();
    const patrol = await markVisited(input, outcome, reviewedAt);

    await enqueue({
        type: 'visit',
        user_id: input.patrol.user_id,
        label: `${input.checkpoint.name} · ${outcome === 'all_clear' ? 'Sin novedad' : 'Cuestionario'}`,
        payload: {
            client_uuid: newUuid(),
            patrol_client_uuid: input.patrol.client_uuid,
            checkpoint_token: input.checkpoint.token,
            outcome,
            is_urgent: input.isUrgent,
            urgent_notes: input.isUrgent ? input.urgentNotes : null,
            reviewed_at: reviewedAt,
            ...extra,
        },
        photos: input.photos,
    });
    await afterChange();

    return patrol;
}

async function markVisited(
    input: Pick<VisitInput, 'patrol' | 'round' | 'checkpoint' | 'isUrgent'>,
    outcome: LocalPatrol['visits'][number]['outcome'],
    reviewedAt: string,
): Promise<LocalPatrol> {
    if (
        input.patrol.visits.some(
            (visit) => visit.checkpoint_id === input.checkpoint.id,
        )
    ) {
        throw new Error('Este punto ya fue revisado en el recorrido actual.');
    }

    const visits = [
        ...input.patrol.visits,
        {
            checkpoint_id: input.checkpoint.id,
            outcome,
            reviewed_at: reviewedAt,
            is_urgent: input.isUrgent,
        },
    ];

    const visitedIds = new Set(visits.map((visit) => visit.checkpoint_id));
    const isComplete = input.round.checkpoints.every((checkpoint) =>
        visitedIds.has(checkpoint.id),
    );

    const patrol: LocalPatrol = {
        ...input.patrol,
        visits,
        finished_at: isComplete ? reviewedAt : null,
    };

    await savePatrol(patrol);

    return patrol;
}

type IncidentInput = {
    userId: number;
    areaId: number;
    message: string;
    isUrgent: boolean;
    photos: File[];
    checkpointContext?: {
        patrol: LocalPatrol;
        round: OfflineRound;
        checkpoint: OfflineCheckpoint;
    };
};

export async function reportIncident(input: IncidentInput): Promise<void> {
    const occurredAt = new Date().toISOString();
    const context = input.checkpointContext;

    if (context) {
        await markVisited(
            { ...context, isUrgent: input.isUrgent },
            'incident',
            occurredAt,
        );
    }

    await enqueue({
        type: 'incident',
        user_id: input.userId,
        label: context
            ? `Incidencia en ${context.checkpoint.name}`
            : `Incidencia: ${input.message.slice(0, 40)}`,
        payload: {
            client_uuid: newUuid(),
            area_id: context ? null : input.areaId,
            message: input.message,
            is_urgent: input.isUrgent,
            checkpoint_token: context?.checkpoint.token ?? null,
            patrol_client_uuid: context?.patrol.client_uuid ?? null,
            visit_client_uuid: context ? newUuid() : null,
            occurred_at: occurredAt,
        },
        photos: input.photos,
    });
    await afterChange();
}

export async function triggerPanic(
    userId: number,
    areaId: number,
    activePatrol: LocalPatrol | null,
): Promise<void> {
    await enqueue({
        type: 'panic',
        user_id: userId,
        label: 'Alerta de pánico',
        payload: {
            client_uuid: newUuid(),
            area_id: areaId,
            patrol_client_uuid: activePatrol?.client_uuid ?? null,
            occurred_at: new Date().toISOString(),
        },
        photos: [],
    });
    await afterChange();
}
