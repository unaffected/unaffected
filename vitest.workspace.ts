import path from 'path'
import { defineWorkspace } from 'vitest/config'
import app from './package/app/vitest.config'
import gateway from './package/gateway/vitest.config'
import renderer from './package/renderer/vitest.config'
import utility from './package/utility/vitest.config'

const packages = [
  app,
  utility,
  gateway,
  renderer,
]

const pkg = (config) => ({
  resolve: {
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app'),
      '@unaffected/gateway': path.resolve(__dirname, 'package/gateway'),
      '@unaffected/renderer': path.resolve(__dirname, 'package/renderer'),
      '@unaffected/utility': path.resolve(__dirname, 'package/utility'),
    },
  },
  test: {
    ...config.test,
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app/source'),
      '@unaffected/gateway': path.resolve(__dirname, 'package/gateway/source'),
      '@unaffected/renderer': path.resolve(__dirname, 'package/renderer/source'),
      '@unaffected/utility': path.resolve(__dirname, 'package/utility/source'),
    },
    environment: 'node',
    globals: true,
    include: ["**/*.test.{ts,tsx}"],
    includeSource: ["**/*.test.{ts,tsx}"],
    typecheck: { tsconfig: './config/typescript/test.json' }
  },
})

export default defineWorkspace(packages.map(pkg))
