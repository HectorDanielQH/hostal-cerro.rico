import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        hmr: {
            host: '127.0.0.1',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                'resources/css/adminlte.css',
                'resources/js/adminlte.js',

                'resources/css/public.css',
                'resources/js/public.js',
            ],
            refresh: true,
        }),
    ],
});
