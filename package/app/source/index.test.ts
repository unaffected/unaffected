import App from '.'
import { Application } from '.'

describe('app', () => {
  test('creating a new application instance', () => {
    const app = new Application()

    expect(typeof app.id).toBe('string')
    expect(typeof app.configure).toBe('function')

    const default_export_app = new App()

    expect(default_export_app).toBeInstanceOf(Application)
  })

  describe('configuring plugins', () => {
    test('configuring a single plugin', async () => {
      const app = new Application() as Application & any

      await app.configure((a: Application & any) => { a.id = 'foo' })

      expect(app.id).toBe('foo')
    })

    test('configuring a collection of plugins', async () => {
      const app = new Application() as Application & any

      await app
        .configure((a: Application & any) => { a.woah = 'howdy' })
        .then(async (b: Application & any) => {
          await app.configure([
            (b: Application & any) => { b.foo = 'foo' },
            (b: Application & any) => { b.bar = 'bar' },
            [(b: Application & any) => { b.baz = 'baz' }],
          ])
        })

      expect(app.woah).toBe('howdy')
      expect(app.foo).toBe('foo')
      expect(app.bar).toBe('bar')
      expect(app.baz).toBe('baz')
    })
  })
})
