export type Subscription = {
  type: string
  handler: (data: any) => void | Promise<void>
}

export class Channel {
  #subscriptions: Subscription[]

  constructor(subscriptions: Subscription[] = []) {
    this.#subscriptions = subscriptions
  }

  get subscriptions() {
    return this.#subscriptions
  }

  publish(event: string, data?: any) {
    const triggers = this.subscriptions
      .filter(({ type }) => type === event.toLowerCase())
      .map(({ handler }) => new Promise(async (resolve, reject) => {
        try {
          resolve(await handler(data))
        } catch (error) {
          reject(error)
        }
      }))

    Promise.all(triggers).catch(() => {})

    return this
  }

  subscribe(type: string, handler: Subscription['handler']) {
    this.subscriptions.push({ handler, type: type.toLowerCase() })

    return this
  }

  unsubscribe(type?: string, handler?: Subscription['handler']) {
    if (!type) {
      this.#subscriptions = []

      return this
    }

    this.#subscriptions = this.#subscriptions.filter((subscription) => {
      if (subscription.type !== type.toLowerCase()) {
        return true
      }

      if (handler && subscription.handler !== handler) {
        return true
      }

      return false
    })

    return this
  }

  emit(type: string, data?: any) {
    return this.publish(type, data)
  }

  on(type: string, handler: Subscription['handler']) {
    return this.subscribe(type, handler)
  }

  off(type?: string, handler?: Subscription['handler']) {
    return this.unsubscribe(type, handler)
  }
}

export default Channel
