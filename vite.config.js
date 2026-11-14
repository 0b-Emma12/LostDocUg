import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const ocrAssets = new Set([
                        'eng.traineddata.gz',
                        'tesseract-core-lstm.wasm.js',
                        'tesseract-core-relaxedsimd-lstm.wasm.js',
                        'tesseract-core-simd-lstm.wasm.js',
                        'worker.min.js',
                    ]);

                    return ocrAssets.has(assetInfo.name)
                        ? `ocr/${assetInfo.name}`
                        : 'assets/[name]-[hash][extname]';
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
