import type Application from '@unaffected/app'
import type { Policy } from '@unaffected/utility/guard'

export interface Command<Input = never, Output = void, P extends Params = never> {
  id: string
  execute: Execute<Input, Output, P>
  authorize?: Policy
}

export type Context<Input = never, Output = void, Params extends Record<string, any> = never> = {
  app: Application
  input: Partial<Input>
  output: Partial<Output>
  params: Params
}

export type Execute<
  Input = never,
  Output = void,
  Params extends Record<string, any> = never
> = (context: Context<Input, Output, Params>) => Promise<Output>

export interface Params {}

export default {}
