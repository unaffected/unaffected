import Channel from '@unaffected/utility/channel'

describe('utility: channel', () => {
  describe('creating a new channel', () => {
    test('creating a channel without initial subscriptions', () => {
      const channel = new Channel()

      expect(channel.subscriptions).toHaveLength(0)
    })

    test('creating a channel with initial subscriptions', () => {
      const subscriptions = [{ type: 'test', execute: () => {} }]
      const channel = new Channel(subscriptions)

      expect(channel.subscriptions).toHaveLength(1)
    })
  })

  test('subscribing to an event', () => {
    const channel = new Channel()

    channel.subscribe('test', () => {})

    expect(channel.subscriptions).toHaveLength(1)
  })

  describe('unsubscribing from events', () => {
    test('removing a specific subscription', () => {
      const subscription = { type: 'test', execute: () => {} }
      const channel = new Channel([subscription])
      channel.unsubscribe(subscription.type, subscription.execute)
      expect(channel.subscriptions).toHaveLength(0)
    })

    test('removing all subscriptions of a specific type', () => {
      const subscription = { type: 'test', execute: () => {} }
      const channel = new Channel([subscription])
      channel.unsubscribe(subscription.type)
      expect(channel.subscriptions).toHaveLength(0)
    })

    test('removing all subscriptions', () => {
      const subscription = { type: 'test', execute: () => {} }
      const channel = new Channel([subscription])
      channel.unsubscribe()
      expect(channel.subscriptions).toHaveLength(0)
    })
  })

  describe('publishing events', () => {
    test('publishing an simple event with no data', async () => {
      let foo = 'foo'

      const channel = new Channel()

      channel.subscribe('test', async () => {
        await Promise.resolve().then(() => { foo = 'bar' })
      })

      channel.publish('test')

      await new Promise((resolve) => {
        setTimeout(() => resolve(true), 15)
      })

      expect(foo).toBe('bar')
    })

    test('publishing an simple event with data', async () => {
      let foo = 'foo'

      const channel = new Channel()

      channel.subscribe('test', async (data: string) => {
        await Promise.resolve().then(() => { foo = data })
      })

      channel.publish('test', 'baz')

      await new Promise((resolve) => setTimeout(() => resolve(true), 15))

      expect(foo).toBe('baz')
    })

    test('errors thrown by subscribers get swallowed silently', async () => {
      let foo = 'foo'

      const channel = new Channel()

      channel.subscribe('test', () => { throw new Error('fail') })
      channel.subscribe('test', () => { foo = 'oof' })

      channel.publish('test')

      await new Promise((resolve) => setTimeout(() => resolve(true), 15))

      expect(foo).toBe('oof')
    })
  })

  test('channel methods are chainable and types are case-insensitive', async () => {
    let foo = 1

    const channel = new Channel([{ type: 'test', execute: () => {} }])

    channel
      .unsubscribe('TEST')
      .subscribe('woot', () => { foo++ })
      .subscribe('WoOt', () => { foo = foo + 2 })
      .publish('WOOT')

    await new Promise((resolve) => setTimeout(() => resolve(true), 15))

    expect(foo).toBe(4)
  })
})
