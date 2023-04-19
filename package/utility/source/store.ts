import { get, set, mergeWith, omit, pick, timer } from '@unaffected/utility'
import type { Timer } from '@unaffected/utility/timer'

export type State<T extends Record<string, any> = Record<string, any>> = T

export interface Timeout<T extends string> {
  key: T
  expiration: number
  created_at: number
  expires_at: number
  timer: NodeJS.Timer | number
  cancel: () => void
}
export class Store<T extends State> {
  private readonly timeouts: Map<string, Timer>
  private readonly initial: Partial<T>
  private state: Partial<T>

  constructor(state: Partial<T> = {}) {
    this.timeouts = new Map()
    this.initial = { ...state }
    this.state = state
  }

  get entries() {
    return Object.entries(this.state)
  }

  get keys() {
    return Object.keys(this.state)
  }

  get timers(): Record<string, Timer> {
    return Object.fromEntries(this.timeouts.entries())
  }

  get values() {
    return Object.values(this.state)
  }

  equals(key: string | string[], value?: any): boolean {
    if (Array.isArray(key)) {
      return key.every((k) => this.equals(k, value))
    }

    return this.get(key) === value
  }

  expire(key: string | string[], expiration: number) {
    if (Array.isArray(key)) {
      key.forEach((k) => this.expire(k, expiration))

      return this
    }

    const timeout = timer.create(() => { this.remove(key) }, expiration)

    this.keep(key)

    this.timeouts.set(key, timeout)

    return this
  }

  expire_at(key: string | string[], expire_at: number | Date) {
    const time = typeof expire_at === 'number' ? expire_at : expire_at.getTime()
    const expiration = time - Date.now()

    if (expiration <= 0) {
      return this.remove(key)
    }

    return this.expire(key, expiration)
  }

  exists(key: string | string[]): boolean {
    if (Array.isArray(key)) {
      return key.every(this.exists.bind(this))
    }

    return [
      this.keys.includes(key),
      this.type(key) !== 'undefined',
    ].every(Boolean)
  }

  get(key?: string, fallback?: any): State<T> | any {
    if (key) {
      return get(this.state, key) ?? fallback
    }

    return this.state
  }

  is(key: string | string[], type: any): boolean {
    if (Array.isArray(key)) {
      return key.every((k) => this.is(k, type))
    }

    if (['boolean', 'object', 'number', 'string', 'undefined'].includes(type)) {
      return this.type(key) === type
    }

    const value = this.get(key) as any

    if (type === 'array') {
      return Array.isArray(value)
    }

    if (type === 'date') {
      return value instanceof Date
    }

    return value === type
  }

  is_array(key: string | string[]) {
    return this.is(key, 'array')
  }

  is_boolean(key: string | string[]) {
    return this.is(key, 'boolean')
  }

  is_date(key: string | string[]) {
    return this.is(key, 'date')
  }

  is_object(key: string | string[]) {
    return this.is(key, 'object')
  }

  is_number(key: string | string[]) {
    return this.is(key, 'number')
  }

  is_string(key: string | string[]) {
    return this.is(key, 'string')
  }

  is_undefined(key: string | string[]) {
    return this.is(key, 'undefined')
  }

  keep(key: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.keep.bind(this))

      return this
    }

    const timeout = this.timeouts.get(key)

    if (timeout) {
      timeout.cancel()
    }

    return this
  }

  merge(state: Partial<T>) {
    this.state = mergeWith(this.state, state, (existing, mutation) => {
      if (Array.isArray(existing)) {
        return [...existing, ...mutation]
      }

      return mutation
    })

    return this
  }

  pick(key: string | string[] = []) {
    return pick(this.state, key as keyof State<T>)
  }

  refresh(key: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.refresh.bind(this))

      return this
    }

    const timeout = this.timeouts.get(key)

    if (timeout) {
      timeout.reset()
    }

    return this
  }

  remove(key: string | string[]) {
    this.state = omit(this.state, key) as Partial<T>

    return this
  }

  replace(state: Partial<T>) {
    this.state = state

    return this
  }

  reset(key?: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.reset.bind(this))

      return this
    }

    if (!key) {
      this.state = this.initial

      return this
    }

    this.set(key, get(this.initial, key))

    return this
  }

  set(key: string, value?: any, expiration?: number) {
    set(this.state, key, value)

    if (expiration) {
      this.expire(key, expiration)
    }

    return this
  }

  take(key: string | string[], fallback?: any) {
    const value = Array.isArray(key) ? this.pick(key) : this.get(key)

    this.remove(key)

    return value
  }

  timer(key: string) {
    return this.timers[key]
  }

  type(key: string) {
    return typeof this.get(key)
  }
}

export default Store
