import lodash from 'lodash'

export { default as Channel } from '@unaffected/utility/channel'
export * as guard from '@unaffected/utility/guard'
export * as pattern from '@unaffected/utility/pattern'
export { default as Store } from '@unaffected/utility/store'
export * as timer from '@unaffected/utility/timer'
export { default as uuid } from '@unaffected/utility/uuid'

export const get = lodash.get
export const keyBy = lodash.keyBy
export const mergeWith = lodash.mergeWith
export const omit = lodash.omit
export const pick = lodash.pick
export const set = lodash.set


