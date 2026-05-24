import { defineConfig } from 'vite';
import { resolve } from 'node:path';

export default defineConfig({
  root: resolve(__dirname),
  base: '/s/',
  build: {
    outDir: resolve(__dirname, '../../dist/scan'),
    emptyOutDir: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/privacy': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
});
