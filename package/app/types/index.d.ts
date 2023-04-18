export type Plugin = (this: Application, app: Application) => void | Promise<void>;
export type Plugins = Array<Plugins | Plugin>;
export declare class Application {
    readonly id: string;
    constructor();
    configure(plugins: Plugin | Plugins): Promise<this>;
}
export default Application;
//# sourceMappingURL=index.d.ts.map