import { defineStore } from 'pinia';

export const useFlashStore = defineStore('flash', {
    state: () => ({
        success: null as string | null,
        error: null as string | null,
    }),
    actions: {
        setSuccess(message: string) {
            this.success = message;
        },
        setError(message: string) {
            this.error = message;
        },
        clear() {
            this.success = null;
            this.error = null;
        },
    },
});
