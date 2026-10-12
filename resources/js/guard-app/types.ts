export type EmergencyContact = {
    name: string;
    phone: string;
};

export type OfflineArea = {
    id: number;
    name: string;
    code: string;
    emergency_contacts: EmergencyContact[];
};

export type OfflineQuestion = {
    id: number;
    body: string;
    options: { id: number; label: string }[];
};

export type OfflineCheckpoint = {
    id: number;
    name: string;
    instructions: string | null;
    position: number;
    token_hash: string;
    questions: OfflineQuestion[];
};

export type OfflineRound = {
    id: number;
    area_id: number;
    title: string;
    instructions: string | null;
    checkpoints: OfflineCheckpoint[];
};

export type OfflinePackage = {
    version: string;
    generated_at: string;
    csrf_token: string;
    user: { id: number; name: string; email: string };
    areas: OfflineArea[];
    rounds: OfflineRound[];
    active_patrol: {
        client_uuid: string;
        round_id: number;
        started_at: string;
        visited_checkpoint_ids: number[];
    } | null;
    branding: {
        primary_color: string;
        light_color: string;
        dark_color: string;
        logo_url: string;
    };
};

export type VisitOutcome = 'questionnaire' | 'all_clear' | 'incident';

export type LocalVisit = {
    checkpoint_id: number;
    outcome: VisitOutcome;
    reviewed_at: string;
    is_urgent: boolean;
};

export type LocalPatrol = {
    client_uuid: string;
    user_id: number;
    round_id: number;
    started_at: string;
    finished_at: string | null;
    visits: LocalVisit[];
};

export type OutboxType = 'patrol' | 'visit' | 'incident' | 'panic';

/**
 * Safari corrupts Blobs read back from IndexedDB when sent in a FormData,
 * so photos are persisted as raw bytes.
 */
export type StoredPhoto = {
    buffer: ArrayBuffer;
    type: string;
};

export type OutboxItem = {
    id?: number;
    type: OutboxType;
    user_id: number;
    label: string;
    payload: Record<string, unknown>;
    photos: Array<StoredPhoto | Blob>;
    status: 'pending' | 'failed';
    attempts: number;
    next_attempt_at: number;
    last_error: string | null;
    created_at: string;
};
