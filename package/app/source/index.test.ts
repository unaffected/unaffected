import App from '@unaffected/app'
import { Application } from '@unaffected/app'

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

      await app.configure({ id: 'foo', install: (a: Application & any) => { a.id = 'foo' } })

      expect(app.id).toBe('foo')

      expect(app.plugins).toEqual(['foo'])
    })

    test('configuring a collection of plugins', async () => {
      const app = new Application() as Application & any

      await app
        .configure({ id: '1', install: (a: Application & any) => { a.woah = 'howdy' } })
        .then(async (b: Application & any) => {
          await b.configure([
            { id: '2', install: (c: Application & any) => { c.foo = 'foo' } },
            { id: '2', install: (c: Application & any) => { c.foo = 'foo' } },
            { id: '3', install: (c: Application & any) => { c.bar = 'bar' } },
            [{ id: '4', install: (c: Application & any) => { c.baz = 'baz' } }],
          ])
        })

      expect(app.woah).toBe('howdy')
      expect(app.foo).toBe('foo')
      expect(app.bar).toBe('bar')
      expect(app.baz).toBe('baz')

      expect(app.plugins).toEqual(['1', '2', '3', '4'])
    })
  })
})
