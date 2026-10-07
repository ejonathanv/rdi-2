import '../../css/app.css';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { GuardApp } from './guard-app';
import { registerServiceWorker } from './register-sw';

const container = document.getElementById('guard-app');

if (container) {
    createRoot(container).render(
        <StrictMode>
            <GuardApp
                logoUrl={container.dataset.logoUrl ?? '/img/rdi-logo.svg'}
            />
        </StrictMode>,
    );
}

void registerServiceWorker({ cacheGuardShell: true });

void navigator.storage?.persist?.();
