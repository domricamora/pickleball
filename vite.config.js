import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import inertia from '@inertiajs/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Plus Jakarta Sans', {
                    weights: [400, 500, 600, 700, 800],
                }),
                // Display face for the marketing headlines. Italic is what
                // carries the sporty slant, so both cuts are shipped.
                bunny('Archivo', {
                    weights: [700, 800, 900],
                    styles: ['normal', 'italic'],
                }),
            ],
        }),
        react(),
        // Client-rendered SPA: there is no SSR entry. Without this the plugin
        // treats app.tsx as one, wraps it in server bootstrap and calls
        // createRoot() with no DOM, which fails every render.
        inertia({ ssr: false }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        // Pin to IPv4: the default localhost resolves to ::1, which a browser
        // opened on 127.0.0.1 cannot reach, leaving a blank page.
        host: '127.0.0.1',
        port: 5173,
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
