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

  test('getting the entries for a store', () => {
    const store = new Store({ foo: 'bar', bar: 'baz' })

    const expected = expect.arrayContaining([
      ['foo', 'bar'],
      ['bar', 'baz'],
    ])

    expect(store.entries).toEqual(expected)
  })

  describe('determining a values type', () => {
    const store = new Store({
      array: ['foo'],
      array_2: ['foo'],
      date: new Date(),
      date_2: new Date(),
      boolean: true,
      boolean_2: true,
      number: 1,
      number_2: 1,
      object: { foo: 'bar' },
      object_2: { foo: 'bar' },
    })

    test('determining if a value is an array', () => {
      expect(store.is('array', 'array')).toBeTruthy()
      expect(store.is('array', 'boolean')).toBeFalsy()
      expect(store.is_array('array')).toBeTruthy()
      expect(store.is_array('boolean')).toBeFalsy()
    })

    test('determining if all values are arrays', () => {
      expect(store.is_array(['array', 'array_2'])).toBeTruthy()
      expect(store.is_array(['array', 'boolean'])).toBeFalsy()
    })

    test('determining if a value is a boolean', () => {
      expect(store.is('boolean', 'boolean')).toBeTruthy()
      expect(store.is('boolean', 'array')).toBeFalsy()
      expect(store.is_boolean('boolean')).toBeTruthy()
      expect(store.is_boolean('array')).toBeFalsy()
    })

    test('determining if all values are booleans', () => {
      expect(store.is_boolean(['boolean', 'boolean_2'])).toBeTruthy()
      expect(store.is_boolean(['boolean', 'array'])).toBeFalsy()
    })

    test('determining if a value is a number', () => {
      expect(store.is('number', 'number')).toBeTruthy()
      expect(store.is('boolean', 'array')).toBeFalsy()
      expect(store.is_number('number')).toBeTruthy()
      expect(store.is_number('array')).toBeFalsy()
    })

    test('determining if all values are numbers', () => {
      expect(store.is_number(['number', 'number_2'])).toBeTruthy()
      expect(store.is_number(['number', 'boolean'])).toBeFalsy()
    })

    test('determining if a value is an object', () => {
      expect(store.is('object', 'object')).toBeTruthy()
      expect(store.is('object', 'number')).toBeFalsy()
      expect(store.is_object('object')).toBeTruthy()
      expect(store.is_object('number')).toBeFalsy()
    })

    test('determining if all values are objects', () => {
      expect(store.is_object(['object', 'object_2'])).toBeTruthy()
      expect(store.is_object(['object', 'boolean'])).toBeFalsy()
    })

    test('determining if a value is a date', () => {
      expect(store.is('date', 'date')).toBeTruthy()
      expect(store.is('date', 'number')).toBeFalsy()
      expect(store.is_date('date')).toBeTruthy()
      expect(store.is_date('number')).toBeFalsy()
    })

    test('determining if all values are dates', () => {
      expect(store.is_date(['date', 'date_2'])).toBeTruthy()
      expect(store.is_date(['date', 'boolean'])).toBeFalsy()
    })

    test('determining if a value is an undefined', () => {
      expect(store.is('undefined', 'undefined')).toBeTruthy()
      expect(store.is('undefined', 'number')).toBeFalsy()
      expect(store.is_undefined('undefined')).toBeTruthy()
      expect(store.is_undefined('number')).toBeFalsy()
    })

    test('determining if all values are undefined', () => {
      expect(store.is_undefined(['undefined', 'undefined_2'])).toBeTruthy()
      expect(store.is_undefined(['undefined', 'boolean'])).toBeFalsy()
    })
  })

  describe('resetting the store state', () => {
    const store = new Store({
      foo: 'bar',
      bar: 'baz',
      baz: 'foo',
    })

    test('resetting a single key', () => {
      store.set('foo', 'baz')

      store.reset('foo')

      expect(store.get('foo')).toBe('bar')
      expect(store.get('bar')).toBe('baz')
      expect(store.get('baz')).toBe('foo')
    })

    test('resetting a multiple keys', () => {
      store.set('foo', 'baz')
      store.set('bar', 'foo')

      store.reset(['foo', 'bar'])

      expect(store.get('foo')).toBe('bar')
      expect(store.get('bar')).toBe('baz')
      expect(store.get('baz')).toBe('foo')
    })

    test('resetting all keys', () => {
      store.set('foo', 'baz')
      store.set('bar', 'foo')

      store.reset()

      expect(store.get('foo')).toBe('bar')
      expect(store.get('bar')).toBe('baz')
      expect(store.get('baz')).toBe('foo')
    })
  })

  test('replacing the store state', () => {
    const store = new Store({ foo: 'bar', bar: 'baz' })

    store.replace({ foo: 'baz', bar: 'foo' })

    expect(store.get('foo')).toBe('baz')
    expect(store.get('bar')).toBe('foo')
  })
})
