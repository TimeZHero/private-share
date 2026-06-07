import { Toaster } from '@/components/atoms/Toaster';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    title: (title) => (title ? `${title}` : 'Private Share'),
    resolve: (name) =>
        resolvePageComponent<{ default: ComponentType }>(
            `./Pages/${name}.tsx`,
            import.meta.glob<{ default: ComponentType }>('./Pages/**/*.tsx'),
        ).then((module) => module.default),
    setup({ el, App, props }) {
        createRoot(el).render(
            <>
                <App {...props} />
                <Toaster />
            </>,
        );
    },
});
