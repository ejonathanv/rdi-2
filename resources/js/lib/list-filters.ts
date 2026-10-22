import { index as incidenciasIndex } from '@/routes/incidencias';
import { index as rondinesIndex } from '@/routes/rondines';

export type IncidentListQuery = {
    from?: string | null;
    to?: string | null;
    category_id?: number | null;
    checkpoint_id?: number | null;
    status?: string | null;
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

    return incidenciasIndex.url({ query: params });
}

export function completedPatrolsUrl(date: string): string {
    return rondinesIndex.url({
        query: {
            from: date,
            to: date,
        },
    });
}
