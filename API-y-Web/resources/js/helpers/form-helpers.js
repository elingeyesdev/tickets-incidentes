/**
 * Form Validation Helpers
 *
 * Utilidades de validación robustas para formularios con Alpine.js
 */

/**
 * Validar formato de email
 * @param {string} email - Email a validar
 * @returns {boolean}
 */
export function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validar contraseña fuerte
 * - Mínimo 8 caracteres
 * - Contiene letras mayúsculas
 * - Contiene letras minúsculas
 * - Contiene números
 * - Contiene caracteres especiales
 * @param {string} password - Contraseña a validar
 * @returns {object} { isValid: boolean, errors: string[] }
 */
export function validateStrongPassword(password) {
    const errors = [];

    if (!password) {
        return { isValid: false, errors: ['La contraseña es requerida'] };
    }

    if (password.length < 8) {
        errors.push('Mínimo 8 caracteres');
    }

    if (!/[A-Z]/.test(password)) {
        errors.push('Debe contener letras mayúsculas');
    }

    if (!/[a-z]/.test(password)) {
        errors.push('Debe contener letras minúsculas');
    }

    if (!/[0-9]/.test(password)) {
        errors.push('Debe contener números');
    }

    if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
        errors.push('Debe contener caracteres especiales');
    }

    return {
        isValid: errors.length === 0,
        errors: errors
    };
}

/**
 * Validar contraseña simple
 * - Mínimo 8 caracteres
 * @param {string} password - Contraseña a validar
 * @returns {boolean}
 */
export function isValidSimplePassword(password) {
    return password && password.length >= 8;
}

/**
 * Validar nombre
 * @param {string} name - Nombre a validar
 * @returns {boolean}
 */
export function isValidName(name) {
    return name && name.trim().length >= 2;
}

/**
 * Validar que dos valores sean iguales
 * @param {*} value1 - Primer valor
 * @param {*} value2 - Segundo valor
 * @returns {boolean}
 */
export function isEqual(value1, value2) {
    return value1 === value2;
}

/**
 * Validar URL
 * @param {string} url - URL a validar
 * @returns {boolean}
 */
export function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * Validar número de teléfono básico
 * @param {string} phone - Teléfono a validar
 * @returns {boolean}
 */
export function isValidPhone(phone) {
    const phoneRegex = /^[+]?[(]?[0-9]{3}[)]?[-\s.]?[0-9]{3}[-\s.]?[0-9]{4,6}$/;
    return phoneRegex.test(phone);
}

/**
 * Sanitizar input de texto
 * @param {string} text - Texto a sanitizar
 * @returns {string}
 */
export function sanitizeText(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Generar error message amigable
 * @param {Error|string} error - Error a procesar
 * @returns {string}
 */
export function getErrorMessage(error) {
    if (error instanceof Error) {
        return error.message;
    }

    if (typeof error === 'string') {
        return error;
    }

    if (error && error.message) {
        return error.message;
    }

    return 'Ocurrió un error desconocido';
}

/**
 * Obtener token CSRF del DOM
 * @returns {string|null}
 */
export function getCsrfToken() {
    const tokenElement = document.querySelector('input[name="_token"]');
    return tokenElement ? tokenElement.value : null;
}

/**
 * Obtener Bearer token del localStorage
 * @returns {string|null}
 */
export function getBearerToken() {
    return localStorage.getItem('access_token');
}

/**
 * Headers estándar para API calls
 * @param {boolean} includeAuth - Incluir token Bearer
 * @returns {object}
 */
export function getApiHeaders(includeAuth = false) {
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': getCsrfToken(),
    };

    if (includeAuth) {
        const token = getBearerToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }
    }

    return headers;
}

/**
 * Hacer request a API con manejo de errores
 * @param {string} url - URL del endpoint
 * @param {object} options - Opciones del fetch
 * @returns {Promise<object>}
 */
export async function apiRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: getApiHeaders(options.includeAuth !== false),
        ...options
    };

    try {
        const response = await fetch(url, defaultOptions);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || `HTTP ${response.status}`);
        }

        return { success: true, data };
    } catch (error) {
        return { success: false, error: getErrorMessage(error) };
    }
}

/**
 * Validar múltiples campos a la vez
 * @param {object} fields - Objeto con campos { fieldName: value }
 * @param {object} rules - Objeto con reglas { fieldName: validatorFunction }
 * @returns {object} { isValid: boolean, errors: { fieldName: errorMessage } }
 */
export function validateFields(fields, rules) {
    const errors = {};
    let isValid = true;

    for (const [fieldName, value] of Object.entries(fields)) {
        if (rules[fieldName]) {
            const validator = rules[fieldName];
            let result;

            if (typeof validator === 'function') {
                result = validator(value);
            } else if (validator.rule && typeof validator.rule === 'function') {
                result = validator.rule(value);
                if (!result && validator.message) {
                    errors[fieldName] = validator.message;
                }
            }

            if (result === false) {
                isValid = false;
                if (!errors[fieldName] && validator.message) {
                    errors[fieldName] = validator.message;
                }
            }
        }
    }

    return { isValid, errors };
}

/**
 * Formatear errores del servidor (Laravel)
 * @param {object} errors - Objeto de errores de validación
 * @returns {object} Errores formateados
 */
export function formatValidationErrors(errors) {
    const formatted = {};

    for (const [field, messages] of Object.entries(errors)) {
        formatted[field] = Array.isArray(messages) ? messages[0] : messages;
    }

    return formatted;
}

/**
 * Debounce function para validaciones
 * @param {function} func - Función a ejecutar
 * @param {number} delay - Delay en ms
 * @returns {function}
 */
export function debounce(func, delay = 300) {
    let timeoutId;

    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func(...args), delay);
    };
}

/**
 * Validar checkbox requerido
 * @param {boolean} value - Valor del checkbox
 * @returns {boolean}
 */
export function isCheckboxChecked(value) {
    return value === true;
}

/**
 * Validar que un campo sea requerido
 * @param {*} value - Valor del campo
 * @returns {boolean}
 */
export function isRequired(value) {
    if (typeof value === 'string') {
        return value.trim().length > 0;
    }
    return value !== null && value !== undefined && value !== '';
}

/**
 * Validar longitud mínima
 * @param {string} value - Valor a validar
 * @param {number} length - Longitud mínima
 * @returns {boolean}
 */
export function minLength(value, length) {
    return value && value.length >= length;
}

/**
 * Validar longitud máxima
 * @param {string} value - Valor a validar
 * @param {number} length - Longitud máxima
 * @returns {boolean}
 */
export function maxLength(value, length) {
    return !value || value.length <= length;
}

/**
 * Validar patrón regex
 * @param {string} value - Valor a validar
 * @param {RegExp} pattern - Patrón a cumplir
 * @returns {boolean}
 */
export function matchesPattern(value, pattern) {
    return pattern.test(value);
}

/**
 * Guardar datos en localStorage de forma segura
 * @param {string} key - Clave
 * @param {*} value - Valor
 */
export function setLocalStorage(key, value) {
    try {
        localStorage.setItem(key, typeof value === 'string' ? value : JSON.stringify(value));
    } catch (e) {
        console.error('Error saving to localStorage:', e);
    }
}

/**
 * Obtener datos de localStorage de forma segura
 * @param {string} key - Clave
 * @returns {*}
 */
export function getLocalStorage(key) {
    try {
        const value = localStorage.getItem(key);
        return value ? (value.startsWith('{') || value.startsWith('[') ? JSON.parse(value) : value) : null;
    } catch (e) {
        console.error('Error reading from localStorage:', e);
        return null;
    }
}

/**
 * Limpiar localStorage
 * @param {string|string[]} keys - Clave(s) a limpiar
 */
export function clearLocalStorage(keys) {
    const keysToClean = Array.isArray(keys) ? keys : [keys];
    keysToClean.forEach(key => localStorage.removeItem(key));
}

export default {
    isValidEmail,
    validateStrongPassword,
    isValidSimplePassword,
    isValidName,
    isEqual,
    isValidUrl,
    isValidPhone,
    sanitizeText,
    getErrorMessage,
    getCsrfToken,
    getBearerToken,
    getApiHeaders,
    apiRequest,
    validateFields,
    formatValidationErrors,
    debounce,
    isCheckboxChecked,
    isRequired,
    minLength,
    maxLength,
    matchesPattern,
    setLocalStorage,
    getLocalStorage,
    clearLocalStorage,
};
