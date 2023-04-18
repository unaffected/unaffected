/// <reference types="node" />
export type Timer = {
    callback: (timer: Timer) => void;
    cancel: () => void;
    created_at: number;
    expires_at: number;
    expiration: number;
    reset: () => void;
    timeout: NodeJS.Timer | number;
};
export declare const create: (callback: (...args: any) => void, expiration: number) => Timer;
export declare const wait: (duration: number) => Promise<unknown>;
export default create;
//# sourceMappingURL=index.d.ts.map