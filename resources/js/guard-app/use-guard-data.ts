import { useCallback, useEffect, useState } from 'react';
import { getKv, outboxItems, patrolsForUser, setKv } from './db';
import { DATA_CHANGED_EVENT, refreshPackage, syncNow } from './store';
import { SYNC_CHANNEL } from './sync';
import type { LocalPatrol, OfflinePackage, OutboxItem } from './types';

const SYNC_INTERVAL_MS = 60 * 1000;

export type GuardData = {
    loading: boolean;
    online: boolean;
    pkg: OfflinePackage | null;
    patrols: LocalPatrol[];
    activePatrol: LocalPatrol | null;
    outbox: OutboxItem[];
    authRequired: boolean;
    selectedAreaId: number | null;
    lastSyncAt: string | null;
    selectArea: (areaId: number) => Promise<void>;
    reload: () => Promise<void>;
};

export function useGuardData(): GuardData {
    const [loading, setLoading] = useState(true);
    const [online, setOnline] = useState(navigator.onLine);
    const [pkg, setPkg] = useState<OfflinePackage | null>(null);
    const [patrols, setPatrols] = useState<LocalPatrol[]>([]);
    const [outbox, setOutbox] = useState<OutboxItem[]>([]);
    const [authRequired, setAuthRequired] = useState(false);
    const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null);
    const [lastSyncAt, setLastSyncAt] = useState<string | null>(null);

    const reload = useCallback(async () => {
        const storedPackage = (await getKv('package')) ?? null;
        const items = await outboxItems();

        setPkg(storedPackage);
        setOutbox(
            storedPackage
                ? items.filter((item) => item.user_id === storedPackage.user.id)
                : items,
        );
        setPatrols(
            storedPackage ? await patrolsForUser(storedPackage.user.id) : [],
        );
        setAuthRequired((await getKv('auth_required')) ?? false);
        setLastSyncAt((await getKv('last_sync_at')) ?? null);

        const storedArea = await getKv('selected_area_id');
        const validArea = storedPackage?.areas.find(
            (area) => area.id === storedArea,
        );

        setSelectedAreaId(validArea?.id ?? storedPackage?.areas[0]?.id ?? null);
        setLoading(false);
    }, []);

    const selectArea = useCallback(
        async (areaId: number) => {
            await setKv('selected_area_id', areaId);
            await reload();
        },
        [reload],
    );

    useEffect(() => {
        const onChange = () => void reload();
        const initialLoad = window.setTimeout(onChange, 0);
        const onOnline = () => {
            setOnline(true);
            void refreshPackage().then(() => syncNow());
        };
        const onOffline = () => setOnline(false);

        window.addEventListener(DATA_CHANGED_EVENT, onChange);
        window.addEventListener('online', onOnline);
        window.addEventListener('offline', onOffline);

        const channel =
            typeof BroadcastChannel !== 'undefined'
                ? new BroadcastChannel(SYNC_CHANNEL)
                : null;
        channel?.addEventListener('message', onChange);

        if (navigator.onLine) {
            void refreshPackage().then(() => syncNow());
        }

        const interval = window.setInterval(() => {
            if (navigator.onLine) {
                void syncNow();
            }
        }, SYNC_INTERVAL_MS);

        return () => {
            window.removeEventListener(DATA_CHANGED_EVENT, onChange);
            window.removeEventListener('online', onOnline);
            window.removeEventListener('offline', onOffline);
            channel?.close();
            window.clearTimeout(initialLoad);
            window.clearInterval(interval);
        };
    }, [reload]);

    return {
        loading,
        online,
        pkg,
        patrols,
        activePatrol:
            patrols.find((patrol) => patrol.finished_at === null) ?? null,
        outbox,
        authRequired,
        selectedAreaId,
        lastSyncAt,
        selectArea,
        reload,
    };
}
