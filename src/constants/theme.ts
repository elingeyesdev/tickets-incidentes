export const colors = {
    primary: '#007bff',    // Acciones principales
    secondary: '#6c757d',  // Acciones secundarias
    success: '#28a745',    // Estados exitosos, ticket resuelto
    warning: '#ffc107',    // Atención, ticket pendiente
    danger: '#dc3545',     // Errores, alertas críticas
    info: '#17a2b8',       // Información

    // Estados de tickets
    ticketOpen: '#28a745',
    ticketPending: '#ffc107',
    ticketResolved: '#17a2b8',
    ticketClosed: '#6c757d',

    // Tipos de anuncios
    maintenance: '#fd7e14',
    incident: '#dc3545',
    news: '#007bff',
    alert: '#6f42c1',

    // Backgrounds
    background: '#f8f9fa',
    surface: '#ffffff',

    // Text
    textPrimary: '#212529',
    textSecondary: '#6c757d',
    textDisabled: '#adb5bd',
};

export const theme = {
    colors,
    spacing: {
        xs: 4,
        sm: 8,
        md: 16,
        lg: 24,
        xl: 32,
    },
    borderRadius: {
        sm: 4,
        md: 8,
        lg: 16,
        xl: 24,
    },
};
