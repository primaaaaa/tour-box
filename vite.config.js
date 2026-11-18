import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';

function copyStaticAssets() {
    return {
        name: 'copy-static-assets',
        closeBundle() {
            const buildDir = path.resolve('public/build/assets');
            const targetCssDir = path.resolve('public/css');
            const targetJsDir = path.resolve('public/js');

            if (!fs.existsSync(targetCssDir)) {
                fs.mkdirSync(targetCssDir, { recursive: true });
            }
            if (!fs.existsSync(targetJsDir)) {
                fs.mkdirSync(targetJsDir, { recursive: true });
            }

            if (fs.existsSync(buildDir)) {
                const files = fs.readdirSync(buildDir);
                const cssFile = files.find(f => f.endsWith('.css'));
                const jsFile = files.find(f => f.endsWith('.js'));

                if (cssFile) {
                    fs.copyFileSync(path.join(buildDir, cssFile), path.join(targetCssDir, 'app.css'));
                    fs.copyFileSync(path.join(buildDir, cssFile), path.resolve('public/app.css'));
                }
                if (jsFile) {
                    fs.copyFileSync(path.join(buildDir, jsFile), path.join(targetJsDir, 'app.js'));
                }
            }
        },
    };
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        copyStaticAssets(),
    ],
    build: {
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name].js',
                chunkFileNames: 'assets/[name].js',
                assetFileNames: 'assets/[name].[ext]',
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
