import { router, usePage } from '@inertiajs/react';
import { driver } from 'driver.js';
import { useCallback, useEffect, useRef, useState } from 'react';
import 'driver.js/dist/driver.css';
import '../../../css/product-tour.css';
import { useSidebar } from '@/components/ui/sidebar';
import { complete as completeProductTour } from '@/routes/product-tour';
import type { AreaSummary } from '@/types';
import { productTourSteps } from './steps';
import type { ProductTourRole } from './steps';
import { ProductTourWelcomeDialog } from './welcome-dialog';

export const PRODUCT_TOUR_START_EVENT = 'rdi-product-tour-start';

type PageProps = {
    auth: {
        user: {
            name: string;
            is_super_admin?: boolean;
            product_tour_completed?: boolean;
            product_tour_eligible?: boolean;
        } | null;
    };
    currentArea: AreaSummary | null;
};

function resolveTourRole(
    isSuperAdmin: boolean,
    currentRole: string | undefined,
): ProductTourRole {
    if (isSuperAdmin || currentRole === 'admin') {
        return 'admin';
    }

    return 'contact';
}

function expandConfigSection(): void {
    const trigger = document.querySelector<HTMLElement>(
        '[data-tour="configuracion"]',
    );

    if (!trigger) {
        return;
    }

    const collapsible = trigger.closest('[data-state]');

    if (collapsible?.getAttribute('data-state') === 'closed') {
        trigger.click();
    }
}

export function ProductTour() {
    const { auth, currentArea } = usePage<PageProps>().props;
    const { open, setOpen, isMobile, setOpenMobile } = useSidebar();
    const [dismissedWelcome, setDismissedWelcome] = useState(false);
    const [processing, setProcessing] = useState(false);
    const activeRef = useRef(false);
    const driverRef = useRef<ReturnType<typeof driver> | null>(null);
    const startingFromWelcomeRef = useRef(false);

    const user = auth.user;
    const eligible = Boolean(user?.product_tour_eligible);
    const completed = Boolean(user?.product_tour_completed);
    const welcomeOpen =
        eligible && !completed && !dismissedWelcome && !processing;

    const markCompleted = useCallback(() => {
        if (processing) {
            return;
        }

        setProcessing(true);
        setDismissedWelcome(true);
        router.post(
            completeProductTour.url(),
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => setProcessing(false),
            },
        );
    }, [processing]);

    const startTour = useCallback(
        (options?: { markOnFinish?: boolean; fromWelcome?: boolean }) => {
            if (!user || activeRef.current) {
                return;
            }

            const markOnFinish = options?.markOnFinish ?? true;

            if (options?.fromWelcome) {
                startingFromWelcomeRef.current = true;
                setDismissedWelcome(true);
            }

            const role = resolveTourRole(
                Boolean(user.is_super_admin),
                currentArea?.role,
            );

            if (isMobile) {
                setOpenMobile(true);
            } else if (!open) {
                setOpen(true);
            }

            window.setTimeout(() => {
                startingFromWelcomeRef.current = false;

                if (role === 'admin') {
                    expandConfigSection();
                }

                window.setTimeout(
                    () => {
                        const steps = productTourSteps(role);

                        if (steps.length === 0) {
                            if (markOnFinish) {
                                markCompleted();
                            }

                            return;
                        }

                        activeRef.current = true;

                        const instance = driver({
                            showProgress: true,
                            animate: true,
                            allowClose: true,
                            overlayOpacity: 0.62,
                            stagePadding: 8,
                            stageRadius: 12,
                            popoverClass: 'rdi-tour-popover',
                            progressText: '{{current}} de {{total}}',
                            nextBtnText: 'Siguiente',
                            prevBtnText: 'Anterior',
                            doneBtnText: 'Finalizar',
                            steps,
                            onDestroyStarted: () => {
                                instance.destroy();
                            },
                            onDestroyed: () => {
                                activeRef.current = false;
                                driverRef.current = null;

                                if (markOnFinish) {
                                    markCompleted();
                                }
                            },
                        });

                        driverRef.current = instance;
                        instance.drive();
                    },
                    role === 'admin' ? 220 : 0,
                );
            }, 180);
        },
        [
            currentArea?.role,
            isMobile,
            markCompleted,
            open,
            setOpen,
            setOpenMobile,
            user,
        ],
    );

    useEffect(() => {
        const onReplay = () => startTour({ markOnFinish: false });

        window.addEventListener(PRODUCT_TOUR_START_EVENT, onReplay);

        return () => {
            window.removeEventListener(PRODUCT_TOUR_START_EVENT, onReplay);
            driverRef.current?.destroy();
        };
    }, [startTour]);

    if (!user || !eligible) {
        return null;
    }

    return (
        <ProductTourWelcomeDialog
            open={welcomeOpen}
            userName={user.name}
            processing={processing}
            onStart={() =>
                startTour({ markOnFinish: true, fromWelcome: true })
            }
            onSkip={() => {
                if (startingFromWelcomeRef.current) {
                    return;
                }

                markCompleted();
            }}
        />
    );
}
