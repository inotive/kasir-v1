import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        ...(process.env.ANALYZE ? [visualizer({ filename: 'public/build/bundle-report.html', template: 'treemap', gzipSize: true, brotliSize: true, open: false })] : []),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('flatpickr')) return 'vendor-flatpickr';
                    if (id.includes('alpinejs')) return 'vendor-alpine';
                    if (id.includes('axios')) return 'vendor-axios';

                    const match = id.match(/node_modules\/(@[^/]+\/)?([^/]+)/);
                    if (!match) return 'vendor';

                    const scope = (match[1] || '').replace('/', '').replace('@', '');
                    const name = match[2] || 'vendor';
                    const chunk = scope ? `${scope}-${name}` : name;
                    return `vendor-${chunk}`;
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
