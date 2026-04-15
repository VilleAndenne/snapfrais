import type { browserSupportsWebAuthn, startAuthentication, startRegistration } from '@simplewebauthn/browser';
import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;

    interface Window {
        browserSupportsWebAuthn: typeof browserSupportsWebAuthn;
        startAuthentication: typeof startAuthentication;
        startRegistration: typeof startRegistration;
    }
}
