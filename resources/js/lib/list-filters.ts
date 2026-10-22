import { index as incidenciasIndex } from '@/routes/incidencias';
import { index as panicosIndex } from '@/routes/panicos';
import { index as rondinesIndex } from '@/routes/rondines';

export type IncidentListQuery = {
    from?: string | null;
    to?: string | null;
    category_id?: number | null;
    checkpoint_id?: number | null;
    status?: string | null;
    is_urgent?: boolean | null;
    open?: boolean | null;
};

export type PatrolListQuery = {
    from?: string | null;
    to?: string | null;
    status?: 'in_progress' | 'completed' | null;
    urgent?: boolean | null;
};

export function incidentListUrl(query: IncidentListQuery = {}): string {
    const params: Record<string, string> = {};

    if (query.from) {
        params.from = query.from;
    }

    if (query.to) {
        params.to = query.to;
    }

    if (query.category_id) {
        params.category_id = String(query.category_id);
    }

    if (query.checkpoint_id) {
        params.checkpoint_id = String(query.checkpoint_id);
    }

    if (query.status) {
        params.status = query.status;
    }

    if (query.is_urgent) {
        params.is_urgent = '1';
    }

    if (query.open) {
        params.open = '1';
    }

    return incidenciasIndex.url({ query: params });
}

export function patrolsUrl(query: PatrolListQuery = {}): string {
    const params: Record<string, string> = {};

    if (query.from) {
        params.from = query.from;
    }

    if (query.to) {
        params.to = query.to;
    }

    if (query.status) {
        params.status = query.status;
    }

    if (query.urgent) {
        params.urgent = '1';
    }

    return rondinesIndex.url({ query: params });
}

export function completedPatrolsUrl(date: string): string {
    return patrolsUrl({
        from: date,
        to: date,
    });
}

export function panicListUrl(query: {
    from?: string | null;
    to?: string | null;
} = {}): string {
    const params: Record<string, string> = {};

    if (query.from) {
        params.from = query.from;
    }

    if (query.to) {
        params.to = query.to;
    }

    return panicosIndex.url({ query: params });
}
