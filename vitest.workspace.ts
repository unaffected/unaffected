import path from 'path'
import { defineWorkspace } from 'vitest/config'
import app from './package/app/vitest.config'
import command from './package/command/vitest.config'

const pkg = (config) => ({
  resolve: {
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app'),
      '@unaffected/command': path.resolve(__dirname, 'package/command'),
    },
  },
  test: {
    ...config.test,
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app/source'),
      '@unaffected/command': path.resolve(__dirname, 'package/command/source'),
    },
    environment: 'node',
    globals: true,
    include: ["**/*.test.{ts,tsx}"],
    includeSource: ["**/*.test.{ts,tsx}"],
    typecheck: { tsconfig: './config/typescript/test.json' }
  },
})


export default defineWorkspace([
  pkg(app),
  pkg(command),
])
