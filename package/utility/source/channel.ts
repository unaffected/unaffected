import uuid from '@unaffected/utility/uuid'
import * as timer from '@unaffected/utility/timer'

export type Options = {
  expire?: number
  limit?: number
  on_expired?: (subscription: Subscription) => any
  unique?: boolean
}

export type Subscription<T extends string = string, D = any> = {
  type: T
  execute: (data: D) => void | Promise<void>
  options?: Options
}

export type Subscriptions = Array<Subscription & {
  id?: string
  invocations?: number
  timer?: timer.Timer
}>

export class Channel {
  #subscriptions: Subscriptions

  constructor(subscriptions: Subscription[] = []) {
    this.#subscriptions = subscriptions
  }

  get subscriptions() {
    return this.#subscriptions
  }

  publish(event: string, data?: any) {
    const triggers = this.#subscriptions
      .map(({ execute, invocations, options, type }, idx) => {
        if (type !== event.toLowerCase()) {
          return false
        }

        this.#subscriptions[idx].invocations = (invocations ?? 0) + 1

        if (options.limit && this.#subscriptions[idx].invocations >= options.limit) {
          this.unsubscribe(type, execute)
        }

        return new Promise(async (resolve, reject) => {
          try {
            resolve(await execute(data))
          } catch (error) {
            reject(error)
          }
        })
      })
      .filter(Boolean)

    Promise.all(triggers).catch(() => {})

    return this
  }

  subscribe(type: string, execute: Subscription['execute'], options: Options = {}) {
    const subscription = {
      execute,
      id: uuid(),
      type: type.toLowerCase(),
      options,
    }

    if (options.expire) {
      this.expire(subscription, options)
    }

    if (options.unique) {
      this.unsubscribe(subscription.type)
    }

    this.subscriptions.push(subscription)

    return this
  }

  unsubscribe(type?: string, execute?: Subscription['execute']) {
    if (!type) {
      this.#subscriptions = []

      return this
    }

    this.#subscriptions = this.#subscriptions.filter((subscription) => {
      if (subscription.type !== type.toLowerCase()) {
        return true
      }

      if (execute && subscription.execute !== execute) {
        return true
      }

      return false
    })

    return this
  }

  emit(type: string, data?: any) {
    return this.publish(type, data)
  }

  on(type: string, execute: Subscription['execute'], options: Options = {}) {
    return this.subscribe(type, execute, options)
  }

  once(type: string, execute: Subscription['execute'], options: Options = {}) {
    options.limit = 1

    return this.subscribe(type, execute, options)
  }

  off(type?: string, execute?: Subscription['execute']) {
    return this.unsubscribe(type, execute)
  }

  private expire(subscription: Subscription, options: Options) {
    timer.create(() => {
      this.unsubscribe(subscription.type, subscription.execute)

      if (options.on_expired) {
        options.on_expired(subscription)
      }
    }, options.expire)
  }
}

export default Channel
