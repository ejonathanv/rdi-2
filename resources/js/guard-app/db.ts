import { openDB } from 'idb';
import type { DBSchema, IDBPDatabase } from 'idb';
import type { LocalPatrol, OfflinePackage, OutboxItem } from './types';

type KvValues = {
    package: OfflinePackage;
    csrf_token: string;
    auth_required: boolean;
    selected_area_id: number;
    last_sync_at: string;
};

interface GuardDb extends DBSchema {
    kv: {
        key: keyof KvValues;
        value: KvValues[keyof KvValues];
    };
    patrols: {
        key: string;
        value: LocalPatrol;
        indexes: { by_user: number };
    };
    outbox: {
        key: number;
        value: OutboxItem;
    };
}

let connection: Promise<IDBPDatabase<GuardDb>> | null = null;

export function db(): Promise<IDBPDatabase<GuardDb>> {
    connection ??= openDB<GuardDb>('rdi-guard', 1, {
        upgrade(database) {
            database.createObjectStore('kv');
            database
                .createObjectStore('patrols', { keyPath: 'client_uuid' })
                .createIndex('by_user', 'user_id');
            database.createObjectStore('outbox', {
                keyPath: 'id',
                autoIncrement: true,
            });
        },
    });

    return connection;
}

export async function getKv<K extends keyof KvValues>(
    key: K,
): Promise<KvValues[K] | undefined> {
    return (await (await db()).get('kv', key)) as KvValues[K] | undefined;
}

export async function setKv<K extends keyof KvValues>(
    key: K,
    value: KvValues[K],
): Promise<void> {
    await (await db()).put('kv', value, key);
}

export async function deleteKv(key: keyof KvValues): Promise<void> {
    await (await db()).delete('kv', key);
}

export async function savePatrol(patrol: LocalPatrol): Promise<void> {
    await (await db()).put('patrols', patrol);
}

export async function patrolsForUser(userId: number): Promise<LocalPatrol[]> {
    return (await db()).getAllFromIndex('patrols', 'by_user', userId);
}

export async function enqueue(
    item: Omit<
        OutboxItem,
        | 'id'
        | 'status'
        | 'attempts'
        | 'next_attempt_at'
        | 'last_error'
        | 'created_at'
    >,
): Promise<void> {
    await (
        await db()
    ).add('outbox', {
        ...item,
        status: 'pending',
        attempts: 0,
        next_attempt_at: 0,
        last_error: null,
        created_at: new Date().toISOString(),
    });
}

export async function outboxItems(): Promise<OutboxItem[]> {
    return (await db()).getAll('outbox');
}

export async function updateOutboxItem(item: OutboxItem): Promise<void> {
    await (await db()).put('outbox', item);
}

export async function deleteOutboxItem(id: number): Promise<void> {
    await (await db()).delete('outbox', id);
}
