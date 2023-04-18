export type Timer = {
  callback: (timer: Timer) => void
  cancel: () => void
  created_at: number
  expires_at: number
  expiration: number
  reset: () => void
  timeout: NodeJS.Timer | number
}

export const create = (callback: (...args: any) => void, expiration: number) => {
  const created_at = Date.now()
  const expires_at = created_at + expiration
  const timeout = setTimeout(() => callback(timer), expiration)

  const cancel = () => clearTimeout(timeout)

  const reset = () => {
    timer.cancel()

    return create(timer.callback, timer.expiration)
  }

  const timer: Timer = {
    callback,
    cancel,
    created_at,
    expiration,
    expires_at,
    timeout,
    reset,
  }

  return timer
}

export const wait = async (duration: number) => new Promise((resolve) => {
  setTimeout(() => { resolve(true) }, duration)
})

export default create
