import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@/components': path.resolve(__dirname, './resources/js/components'),
            '@/layouts': path.resolve(__dirname, './resources/js/layouts'),
            '@/pages': path.resolve(__dirname, './resources/js/pages'),
            '@/features': path.resolve(__dirname, './resources/js/features'),
            '@/lib': path.resolve(__dirname, './resources/js/lib'),
            '@/hooks': path.resolve(__dirname, './resources/js/hooks'),
            '@/contexts': path.resolve(__dirname, './resources/js/contexts'),
            '@/types': path.resolve(__dirname, './resources/js/types'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: {
            host: 'localhost',
        },
    },
});
