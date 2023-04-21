import Application from '@unaffected/app'

export type Context<Input = any, Output = any, Params extends Record<string, any> = any> = {
  app: Application
  input: Input
  params?: Params
  output?: Output
}

export interface Params {}

export abstract class Command<Input = any, Output = any, P extends Params = any> {
  public abstract readonly id: string
  abstract execute(context: Context<Input, Output, P>): Output | Promise<Output>
  abstract authorize?(context: Context<Input, Output, P>): Promise<boolean>
}

export interface Commands {}

export default {}
