import { useCallback, useEffect, useState } from 'react';

export type GuardRoute =
    | { name: 'home' }
    | { name: 'rounds' }
    | { name: 'patrol' }
    | { name: 'checkpoint'; checkpointId: number }
    | { name: 'incident'; checkpointId: number | null }
    | { name: 'outbox' };

function parse(hash: string): GuardRoute {
    const [, section, param] = hash.replace(/^#/, '').split('/');
    const id = Number(param);

    switch (section) {
        case 'recorridos':
            return { name: 'rounds' };
        case 'recorrido':
            return { name: 'patrol' };
        case 'punto':
            return Number.isInteger(id) && id > 0
                ? { name: 'checkpoint', checkpointId: id }
                : { name: 'patrol' };
        case 'incidencia':
            return {
                name: 'incident',
                checkpointId: Number.isInteger(id) && id > 0 ? id : null,
            };
        case 'pendientes':
            return { name: 'outbox' };
        default:
            return { name: 'home' };
    }
}

export function routeHash(route: GuardRoute): string {
    switch (route.name) {
        case 'rounds':
            return '#/recorridos';
        case 'patrol':
            return '#/recorrido';
        case 'checkpoint':
            return `#/punto/${route.checkpointId}`;
        case 'incident':
            return route.checkpointId
                ? `#/incidencia/${route.checkpointId}`
                : '#/incidencia';
        case 'outbox':
            return '#/pendientes';
        default:
            return '#/';
    }
}

export function useHashRoute(): [GuardRoute, (route: GuardRoute) => void] {
    const [route, setRoute] = useState<GuardRoute>(() =>
        parse(window.location.hash),
    );

    useEffect(() => {
        const onChange = () => {
            setRoute(parse(window.location.hash));
            window.scrollTo({ top: 0 });
        };

        window.addEventListener('hashchange', onChange);

        return () => window.removeEventListener('hashchange', onChange);
    }, []);

    const navigate = useCallback((next: GuardRoute) => {
        window.location.hash = routeHash(next);
    }, []);

    return [route, navigate];
}
