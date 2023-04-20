import pattern from 'micromatch'

export const is_match = pattern.isMatch
export const match = pattern.match
export const match_keys = pattern.matchKeys
export const create = pattern.matcher
