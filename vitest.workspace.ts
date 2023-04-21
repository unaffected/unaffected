import path from 'path'
import { defineWorkspace } from 'vitest/config'
import app from './package/app/vitest.config'
import command from './package/command/vitest.config'
import utility from './package/utility/vitest.config'
import gateway from './service/gateway/vitest.config'

const packages = [
  app,
  command,
  utility,
  gateway,
]

const pkg = (config) => ({
  resolve: {
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app'),
      '@unaffected/command': path.resolve(__dirname, 'package/command'),
      '@unaffected/utility': path.resolve(__dirname, 'package/utility'),
    },
  },
  test: {
    ...config.test,
    alias: {
      '@unaffected/app': path.resolve(__dirname, 'package/app/source'),
      '@unaffected/command': path.resolve(__dirname, 'package/command/source'),
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
