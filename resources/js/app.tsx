import { Toaster } from '@/components/atoms/Toaster';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

void createInertiaApp({
    title: (title) => (title ? `${title}` : 'Private Share'),
    pages: {
        path: './Pages',
        extension: '.tsx',
        lazy: true,
    },
    setup({ el, App, props }) {
        if (!el) {
            throw new Error('Inertia root element was not found.');
        }

        createRoot(el).render(
            <>
                <App {...props} />
                <Toaster />
            </>,
        );
    },
});
