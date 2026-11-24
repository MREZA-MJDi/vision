import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/storefront.css',
                'resources/css/home-hero.css',
                'resources/js/app.js',
                'resources/js/storefront.js',
                'resources/css/vision-shop.css',
                'resources/css/fitting-room-studio.css',
                'resources/js/fitting-room-studio.js',
                'resources/js/vision-shop.js',
                'packages/RM/CommerceAdmin/resources/css/admin-kit.css',
                'packages/RM/CommerceAdmin/resources/js/admin-kit.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
