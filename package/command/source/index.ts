import type Application from '@unaffected/app'

export type Context<Input = never, Output = void, Params extends Record<string, any> = never> = {
  app: Application
  input: Partial<Input>
  output: Partial<Output>
  params: Params
}

export interface Params {}

export abstract class Command<Input = never, Output = void, P extends Params = never> {
  public abstract readonly id: string
  abstract authorize(context: Context<Input, Output, P>): Promise<boolean>
  abstract execute(context: Context<Input, Output, P>): Promise<Output>
}

export default {}
