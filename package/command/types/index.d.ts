import Application from '@unaffected/app';
export interface Command<T = never> {
    id: string;
    execute: (app: Application) => (data: T) => Promise<any>;
}
declare const app: Application;
export default app;
//# sourceMappingURL=index.d.ts.map