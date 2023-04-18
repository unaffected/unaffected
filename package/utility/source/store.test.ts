import Store from './store'

describe('utility: store', () => {
  test('creating a new store', () => {
    const store = new Store()

    expect(store).toBeInstanceOf(Store)
  })

  describe('getting and setting store values', () => {
    test('setting and getting values', () => {
      const store = new Store()

      expect(store.get('foo')).toBeUndefined()

      store.set('foo', 'bar')

      expect(store.get('foo')).toBe('bar')
    })

    test('returns the fallback if the key is undefined', () => {
      const store = new Store()

      expect(store.get('foo')).toBeUndefined()
      expect(store.get('foo', 'bar')).toBe('bar')
    })

    test('returns the entire state if no key is given', () => {
      const state = { foo: 'bar', bar: { baz: 'foo' } }
      const store = new Store(state)

      expect(store.get()).toBe(state)
    })
  })
})
