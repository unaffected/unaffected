import * as dot from 'dot-object'
import type { Timer } from '@unaffected/utility/timer'
import * as timer from '@unaffected/utility/timer'

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
  #initial: Partial<T>
  #state: Partial<T>
  #timers: Map<string, Timer>

  constructor(state: Partial<T> = {}) {
    this.#initial = { ...state }
    this.#state = state
    this.#timers = new Map()
  }

  get entries() {
    return Object.entries(this.state)
  }

  get keys() {
    return Object.keys(this.state)
  }

  get state() {
    return this.#state
  }

  get timers(): Record<string, Timer> {
    return Object.fromEntries(this.#timers.entries())
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

    this.#timers.set(key, timeout)

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
      return dot.pick(key, this.#state) ?? fallback
    }

    return this.#state
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

    const timeout = this.#timers.get(key)

    if (timeout) {
      timeout.cancel()
    }

    return this
  }

  merge(state: Partial<T>) {
    dot.set(undefined, state, this.#state, true)

    return this
  }

  pick(key: string | string[] = []) {
    if (Array.isArray(key)) {
      return key.reduce((acc, k) => ({ ...acc, ...this.pick(k) }), {})
    }

    return { [key]: dot.pick(key, this.#state) }
  }

  refresh(key: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.refresh.bind(this))

      return this
    }

    const timeout = this.#timers.get(key)

    if (timeout) {
      timeout.reset()
    }

    return this
  }

  remove(key: string | string[]) {
    this.#state = dot.remove(key, this.#state) as Partial<T>

    return this
  }

  replace(state: Partial<T>) {
    this.#state = state

    return this
  }

  reset(key?: string | string[]) {
    if (Array.isArray(key)) {
      key.forEach(this.reset.bind(this))

      return this
    }

    if (!key) {
      this.#state = this.#initial

      return this
    }

    dot.set(key, dot.pick(key, this.#initial), this.#state)

    return this
  }

  set(key: string, value?: any, expiration?: number) {
    dot.set(key, value, this.#state)

    if (expiration) {
      this.expire(key, expiration)
    }

    return this
  }

  take(key: string | string[]) {
    if (Array.isArray(key)) {
      return key.reduce((acc, k) => ({ ...acc, [k]: this.take(k) }), {})
    }

    return dot.pick(key, this.#state, true)
  }

  timer(key: string) {
    return this.timers[key]
  }

  type(key: string) {
    return typeof this.get(key)
  }
}

export default Store
