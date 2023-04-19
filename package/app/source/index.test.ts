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
          await b.configure([
            (c: Application & any) => { c.foo = 'foo' },
            (c: Application & any) => { c.bar = 'bar' },
            [(c: Application & any) => { c.baz = 'baz' }],
          ])
        })

      expect(app.woah).toBe('howdy')
      expect(app.foo).toBe('foo')
      expect(app.bar).toBe('bar')
      expect(app.baz).toBe('baz')
    })
  })
})
