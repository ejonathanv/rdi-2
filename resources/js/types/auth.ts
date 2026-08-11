export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    is_super_admin?: boolean;
    can_manage_areas?: boolean;
    can_manage_users?: boolean;
    can_view_operations?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type AreaSummary = {
    id: number;
    name: string;
    code: string;
    is_active?: boolean;
    role?: string;
};

export type AreaMembership = {
    area_id: number;
    area_name?: string;
    area_code?: string;
    role: string;
};

export type RoleOption = {
    value: string;
    label: string;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
