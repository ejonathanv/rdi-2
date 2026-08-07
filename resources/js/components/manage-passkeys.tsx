import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

/**
 * Passkeys desactivadas temporalmente (Features::passkeys en config/fortify.php).
 * Stub para no romper el build mientras Wayfinder no genera las acciones.
 */
export default function ManagePasskeys(_props: Props) {
    return null;
}
