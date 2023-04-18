import { create, wait } from '.'

describe('utility: timer', async () => {
  test('creating a timer', async () => {
    let foo = 'foo'

    const timer = create(() => { foo = 'bar' }, 20)

    expect(timer.expiration).toBe(20)
    expect(typeof timer.cancel).toBe('function')
    expect(typeof timer.created_at).toBe('number')
    expect(typeof timer.expires_at).toBe('number')
    expect(typeof timer.timeout).not.toBeUndefined()

    expect(foo).toBe('foo')

    await wait(25)

    expect(foo).toBe('bar')
  })

  test('canceling a timer', async () => {
    let foo = 'foo'

    const timer = create(() => {
      expect(foo).toBe('bar')
    }, 20)

    timer.cancel()

    expect(foo).toBe('foo')

    await wait(25)

    expect(foo).toBe('foo')
  })

  test('resetting a timer', async () => {
    let foo = 'foo'

    const timer = create(() => { foo = 'bar' }, 10)

    await wait(5)

    timer.reset()

    await wait(6)

    expect(foo).toBe('foo')

    await wait(10)

    expect(foo).toBe('bar')
  })

  test('manually invoking a timer callback', () => {
    let foo = 'foo'

    const timer = create(() => { foo = 'bar' }, 100)

    timer.callback(timer)

    expect(foo).toBe('bar')
    expect(timer.expires_at).toBeGreaterThan(Date.now())

    timer.cancel()
  })
})
