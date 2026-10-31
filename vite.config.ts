import inertia from '@inertiajs/vite';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    // Behind the shared Caddy proxy (compose.yaml) the dev server is reached on
    // https://vite.<folder>.localhost:443, not on the container's port 5173.
    server: process.env.VITE_DEV_SERVER_URL
        ? {
              origin: process.env.VITE_DEV_SERVER_URL,
              hmr: { clientPort: 443 },
              // The page lives on APP_URL, a different origin from the dev server.
              cors: { origin: process.env.APP_URL },
          }
        : undefined,
});
