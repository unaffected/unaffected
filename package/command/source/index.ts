import Application from '@unaffected/app'

export type Context<Input = never, Output = void> = {
  input: Partial<Input>
  output: Partial<Output>
}

export interface Command<Input = never, Output = void> {
  id: string
  execute: (app: Application) => (data: Input) => Promise<Output>
}

export default {}
