import { describe, expect, test } from 'bun:test'
import Application from './app'

describe('app', () => {
  test('creating a new application instance', () => {
    const app = new Application()
    expect(typeof app.created_at).toBe('number')
    expect(typeof app.configure).toBe('function')
  })

  describe('configuring plugins', () => {
    test('configuring a single plugin', async () => {
      const app = new Application() as Application & any
      await app.configure((a: Application & any) => { a.id = 'foo' })
      expect(app.id).toBe('foo')
    })

    test('configuring a collection of plugins', async () => {
      const app = new Application() as Application & any

      await app.configure([
        (a: Application & any) => { a.foo = 'foo' },
        (a: Application & any) => { a.bar = 'bar' },
        [
          (a: Application & any) => { a.baz = 'baz' },
        ]
      ])

      expect(app.foo).toBe('foo')
      expect(app.bar).toBe('bar')
      expect(app.baz).toBe('baz')
    })
  })
})
