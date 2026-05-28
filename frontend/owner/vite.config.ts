import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { VitePWA } from 'vite-plugin-pwa';
import { resolve } from 'node:path';

/**
 * Owner-панель монтируется на `/owner` поддомена тенанта
 * ({slug}.otziv.space/owner). На сервере SPA-shell отдаёт Laravel-контроллер
 * (см. backend `OwnerSpaController`). На dev — Vite proxy в API/sanctum.
 */
export default defineConfig({
  root: resolve(__dirname),
  base: '/owner/',
  plugins: [
    react(),
    VitePWA({
      // Свой SW (src/sw.ts) — нужен, чтобы перехватывать `push` и
      // `notificationclick` event'ы. Workbox-precache и runtime-caching
      // остаются — собираются внутри sw.ts.
      strategies: 'injectManifest',
      srcDir: 'src',
      filename: 'sw.ts',
      registerType: 'autoUpdate',
      includeAssets: ['favicon.svg', 'icons/icon-192.png', 'icons/icon-512.png'],
      manifest: {
        name: 'Guard Reviews — Кабинет',
        short_name: 'GR Кабинет',
        description: 'Личный кабинет владельца',
        start_url: '/owner/',
        scope: '/owner/',
        display: 'standalone',
        background_color: '#FAFAF7',
        theme_color: '#FAFAF7',
        lang: 'ru',
        icons: [
          { src: 'icons/icon-192.png', sizes: '192x192', type: 'image/png' },
          { src: 'icons/icon-512.png', sizes: '512x512', type: 'image/png' },
          { src: 'icons/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
        ],
      },
      injectManifest: {
        // navigateFallback из workbox-generation нам не нужен в injectManifest —
        // SW сам делает это через workbox-routing.NavigationRoute.
        globPatterns: ['**/*.{js,css,html,svg,png,ico}'],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  build: {
    outDir: resolve(__dirname, '../../dist/owner'),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: resolve(__dirname, 'index.html'),
      },
    },
  },
  server: {
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/sanctum': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test-setup.ts'],
  },
});
