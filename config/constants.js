// Configuración global
export const API = {
    CHAT: {
        INTERVENE: '/chat/intervene/',
        MESSAGES: '/chat/messages/',
        CLEAR: '/chat/clear/',
        DELETE_CONTACT: '/chat/delete-contact/',
        CLEAR_ORDER: '/chat/clear-order/'
    },
    CONTACT: {
        INFO: '/contact-info/',
        TAGS: '/contacts/'
    },
    INVENTORY: {
        PRODUCTS: '/inventory/products',
        SAVE: '/inventory/save',
        UPDATE: '/inventory/update/',
        DELETE: '/inventory/delete/',
        GET_ONE: '/inventory/product/'
    },
    QUICK_RESPONSES: {
        GET: '/quick-responses',
        SAVE: '/quick-responses/save',
        DELETE: '/quick-responses/delete/'
    }
};

export const N8N_CONFIG = {
    WEBHOOK_URL: "https://malacological-nathalie-unhermitic.ngrok-free.dev/webhook-test/sync-contact-whatsapp"
};

export const UI = {
    REFRESH_INTERVAL: 3000
};