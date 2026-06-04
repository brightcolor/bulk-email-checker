import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '~bootstrap': resolve('./node_modules/bootstrap'),
            '~admin-lte': resolve('./node_modules/admin-lte'),
            '~overlayscrollbars': resolve('./node_modules/overlayscrollbars'),
            '~@fortawesome': resolve('./node_modules/@fortawesome'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
