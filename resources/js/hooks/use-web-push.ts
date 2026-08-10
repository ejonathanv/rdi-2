import { useHttp, usePage } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { store as storeSubscription } from '@/routes/push-subscriptions';

export type WebPushStatus =
    | 'loading'
    | 'unsupported'
    | 'denied'
    | 'prompt'
    | 'active'
    | 'error';

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i++) {
        outputArray[i] = rawData.charCodeAt(i);
    }

    return outputArray;
}

type PushSubscriptionPayload = {
    endpoint: string;
    keys: {
        p256dh: string;
        auth: string;
    };
    content_encoding: string;
};

type UseWebPushResult = {
    status: WebPushStatus;
    enable: () => Promise<void>;
};

export function useWebPush(): UseWebPushResult {
    const { setData, submit } = useHttp<PushSubscriptionPayload>({
        endpoint: '',
        keys: {
            p256dh: '',
            auth: '',
        },
        content_encoding: 'aes128gcm',
    });
    const { auth, vapidPublicKey, hasPushSubscription } = usePage().props;
    const [status, setStatus] = useState<WebPushStatus>(() => {
        if (!auth.user) {
            return 'unsupported';
        }

        if (hasPushSubscription) {
            return 'active';
        }

        return 'loading';
    });
    const attempted = useRef(false);

    const syncSubscription = useCallback(
        async (requestPermission: boolean): Promise<WebPushStatus> => {
            if (
                !auth.user ||
                !vapidPublicKey ||
                !('serviceWorker' in navigator) ||
                !('PushManager' in window) ||
                !('Notification' in window)
            ) {
                return 'unsupported';
            }

            const registration = await navigator.serviceWorker.register('/sw.js');
            await navigator.serviceWorker.ready;

            const notificationApi = window.Notification;
            let permission = notificationApi.permission;

            if (permission === 'default' && requestPermission) {
                permission = await notificationApi.requestPermission();
            }

            if (permission === 'denied') {
                return 'denied';
            }

            if (permission !== 'granted') {
                return 'prompt';
            }

            const existing = await registration.pushManager.getSubscription();
            const subscription =
                existing ??
                (await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(
                        vapidPublicKey,
                    ) as BufferSource,
                }));

            const json = subscription.toJSON();

            if (!json.endpoint || !json.keys?.p256dh || !json.keys?.auth) {
                return 'error';
            }

            const payload: PushSubscriptionPayload = {
                endpoint: json.endpoint,
                keys: {
                    p256dh: json.keys.p256dh,
                    auth: json.keys.auth,
                },
                content_encoding: 'aes128gcm',
            };

            setData(payload);
            await submit(storeSubscription());

            return 'active';
        },
        [auth.user, setData, submit, vapidPublicKey],
    );

    const enable = useCallback(async (): Promise<void> => {
        setStatus('loading');

        try {
            const next = await syncSubscription(true);
            setStatus(next);
        } catch (error) {
            console.error('No se pudo activar Web Push', error);
            setStatus('error');
        }
    }, [syncSubscription]);

    useEffect(() => {
        if (attempted.current || !auth.user) {
            return;
        }

        attempted.current = true;

        const notificationApi =
            typeof window !== 'undefined' && 'Notification' in window
                ? window.Notification
                : null;

        void syncSubscription(notificationApi?.permission === 'granted')
            .then(setStatus)
            .catch((error: unknown) => {
                console.error('No se pudo registrar Web Push', error);
                setStatus('error');
            });
    }, [auth.user, syncSubscription]);

    return { status, enable };
}
