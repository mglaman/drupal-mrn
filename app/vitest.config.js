import { defineConfig } from 'vitest/config'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import postcss from './postcss.config.js'

export default defineConfig({
  plugins: [
    svelte({
      // Disable SSR for tests
      compilerOptions: {
        hydratable: false
      }
    })
  ],
  resolve: {
    conditions: ['browser', 'module', 'import', 'default']
  },
  css: {
    postcss
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./tests/setup.js'],
    include: ['tests/**/*.test.{js,ts}'],
    exclude: ['tests/visual/**', 'node_modules/**', 'dist/**'],
    coverage: {
      provider: 'v8',
      reporter: ['text', 'json', 'html'],
      exclude: [
        'node_modules/',
        'tests/',
        'dist/',
        '*.config.js',
        '*.config.mjs',
        '*.config.cjs'
      ]
    }
  }
})

