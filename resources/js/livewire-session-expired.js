export const SESSION_EXPIRED_MESSAGE =
    'Sua sessão expirou por segurança.\nRecarregar a página para continuar?';

/**
 * Replaces Livewire's English "page expired" dialog (HTTP 419) with a pt-BR one,
 * asking only once even when several requests fail together.
 */
export function createSessionExpiredHandler(confirmReload, reload) {
    let alreadyAsked = false;

    return ({ response, preventDefault }) => {
        if (response?.status !== 419) {
            return;
        }

        preventDefault();

        if (alreadyAsked) {
            return;
        }

        alreadyAsked = true;

        if (confirmReload(SESSION_EXPIRED_MESSAGE)) {
            reload();
        }
    };
}
