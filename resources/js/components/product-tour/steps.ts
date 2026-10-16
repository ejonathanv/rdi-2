import type { DriveStep } from 'driver.js';

export type ProductTourRole = 'admin' | 'contact';

const areaStep: DriveStep = {
    element: '[data-tour="area-switcher"]',
    popover: {
        title: 'Cambia de área o planta',
        description:
            'Si operas en varias plantas, selecciona aquí el área activa. Toda la información del panel se filtra por esta selección.',
        side: 'right',
        align: 'start',
    },
};

const incidenciasStep: DriveStep = {
    element: '[data-tour="incidencias"]',
    popover: {
        title: 'Incidencias',
        description:
            'Consulta, filtra y da seguimiento a los reportes de los guardias. Desde aquí cierras o actualizas el estado de cada incidencia.',
        side: 'right',
        align: 'start',
    },
};

const rondinesStep: DriveStep = {
    element: '[data-tour="rondines"]',
    popover: {
        title: 'Rondines',
        description:
            'Revisa el historial de recorridos realizados: puntos visitados, tiempos, urgencias y evidencia fotográfica.',
        side: 'right',
        align: 'start',
    },
};

const recorridosStep: DriveStep = {
    element: '[data-tour="recorridos"]',
    popover: {
        title: 'Recorridos y cuestionarios',
        description:
            'Configura las rutas de patrullaje, sus puntos QR y, en cada punto, el cuestionario que el guardia debe responder.',
        side: 'right',
        align: 'start',
    },
};

const pushStep: DriveStep = {
    element: '[data-tour="push-notifications"]',
    popover: {
        title: 'Notificaciones push',
        description:
            'Actívalas para recibir alertas en el navegador cuando haya pánicos, incidencias o visitas urgentes, incluso con la pestaña en segundo plano.',
        side: 'bottom',
        align: 'end',
    },
};

const bellStep: DriveStep = {
    element: '[data-tour="notification-bell"]',
    popover: {
        title: 'Centro de alertas',
        description:
            'Aquí verás el historial de notificaciones del sistema. El indicador te avisa cuando hay mensajes sin leer.',
        side: 'bottom',
        align: 'end',
    },
};

export function productTourSteps(role: ProductTourRole): DriveStep[] {
    const steps: DriveStep[] = [areaStep, incidenciasStep, rondinesStep];

    if (role === 'admin') {
        steps.push(recorridosStep);
    }

    steps.push(pushStep, bellStep);

    return steps.filter((step) => {
        if (typeof step.element !== 'string') {
            return true;
        }

        return document.querySelector(step.element) !== null;
    });
}
