import type { Plugin } from '@unaffected/app';
import timer from '.';
declare module '@unaffected/app' {
    interface Application {
        timer: typeof timer;
    }
}
export declare const plugin: Plugin;
export default plugin;
//# sourceMappingURL=plugin.d.ts.map