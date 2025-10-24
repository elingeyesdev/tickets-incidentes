/**
 * PersistenceService.ts
 *
 * Proporciona una capa de abstracción para la persistencia de datos de sesión,
 * con una estrategia de fallback inteligente (IndexedDB -> localStorage -> in-memory).
 */

import { authLogger } from './constants';
import type { AccessToken } from './types';

// ============================================================================
// TIPOS Y INTERFACES
// ============================================================================

const DB_NAME = 'HelpdeskAuthDB';
const DB_VERSION = 1;
const STORE_NAME = 'authState';
const KEY = 'session';

// Interfaz para los datos que realmente se guardarán
interface PersistedData {
    accessToken: AccessToken;
    user: unknown;
    roleContexts: unknown[];
    lastSelectedRole: string | null;
    version: number;
}

// Interfaz para los diferentes motores de almacenamiento
interface StorageBackend {
    get(): Promise<PersistedData | null>;
    set(data: PersistedData): Promise<void>;
    clear(): Promise<void>;
}

// ============================================================================
// IMPLEMENTACIONES DE BACKEND
// ============================================================================

class IndexedDBBackend implements StorageBackend {
    private db: Promise<IDBDatabase>;

    constructor() {
        this.db = new Promise((resolve, reject) => {
            try {
                const request = indexedDB.open(DB_NAME, DB_VERSION);
                request.onerror = () => reject(request.error);
                request.onsuccess = () => resolve(request.result);
                request.onupgradeneeded = () => {
                    const db = request.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'key' });
                    }
                };
            } catch (error) {
                reject(error);
            }
        });
    }

    private async getStore(mode: IDBTransactionMode): Promise<IDBObjectStore> {
        const db = await this.db;
        return db.transaction(STORE_NAME, mode).objectStore(STORE_NAME);
    }

    async get(): Promise<PersistedData | null> {
        const store = await this.getStore('readonly');
        const request = store.get(KEY);
        return new Promise((resolve, reject) => {
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve(request.result?.data ?? null);
        });
    }

    async set(data: PersistedData): Promise<void> {
        try {
            console.log('[IndexedDB] Getting store...');
            const store = await this.getStore('readwrite');
            console.log('[IndexedDB] Store acquired, putting data...');
            const request = store.put({ key: KEY, data });
            return new Promise((resolve, reject) => {
                request.onerror = () => {
                    console.error('[IndexedDB] Put failed:', request.error);
                    reject(request.error);
                };
                request.onsuccess = () => {
                    console.log('[IndexedDB] Put successful');
                    resolve();
                };
            });
        } catch (error) {
            console.error('[IndexedDB] Set error:', error);
            throw error;
        }
    }

    async clear(): Promise<void> {
        const store = await this.getStore('readwrite');
        const request = store.delete(KEY);
        return new Promise((resolve, reject) => {
            request.onerror = () => reject(request.error);
            request.onsuccess = () => resolve();
        });
    }
}

class LocalStorageBackend implements StorageBackend {
    private readonly key = `${DB_NAME}_${STORE_NAME}`;

    async get(): Promise<PersistedData | null> {
        const data = localStorage.getItem(this.key);
        return data ? JSON.parse(data) : null;
    }

    async set(data: PersistedData): Promise<void> {
        localStorage.setItem(this.key, JSON.stringify(data));
    }

    async clear(): Promise<void> {
        localStorage.removeItem(this.key);
    }
}

class InMemoryBackend implements StorageBackend {
    private storage: PersistedData | null = null;

    async get(): Promise<PersistedData | null> {
        return this.storage;
    }

    async set(data: PersistedData): Promise<void> {
        this.storage = data;
    }

    async clear(): Promise<void> {
        this.storage = null;
    }
}

// ============================================================================
// SERVICIO PRINCIPAL DE PERSISTENCIA
// ============================================================================

class PersistenceServiceClass {
    private backend: StorageBackend;

    constructor() {
        this.backend = this.determineBestBackend();
    }

    private determineBestBackend(): StorageBackend {
        try {
            if (window.indexedDB) {
                authLogger.info('PersistenceService: Using IndexedDB backend.');
                return new IndexedDBBackend();
            }
        } catch (e) {
            authLogger.error('IndexedDB check failed, falling back.', e);
        }

        try {
            if (window.localStorage) {
                authLogger.info('PersistenceService: Using LocalStorage backend.');
                return new LocalStorageBackend();
            }
        } catch (e) {
            authLogger.error('LocalStorage check failed, falling back.', e);
        }

        authLogger.warn('PersistenceService: Using In-Memory backend. Session will not persist.');
        return new InMemoryBackend();
    }

    async saveState(data: Omit<PersistedData, 'version'>): Promise<void> {
        const dataToPersist: PersistedData = {
            ...data,
            version: DB_VERSION,
        };

        if (dataToPersist.accessToken.expiresAt < Date.now()) {
            authLogger.warn('PersistenceService: Attempted to save an already expired session. Clearing instead.');
            await this.clearState();
            return;
        }
        try {
            console.log('[PersistenceService] Starting saveState...', { backend: this.backend.constructor.name });
            await this.backend.set(dataToPersist);
            console.log('[PersistenceService] saveState completed successfully');
        } catch (error) {
            console.error('[PersistenceService] saveState failed:', error);
            throw error;
        }
    }

    async loadState(): Promise<PersistedData | null> {
        const data = await this.backend.get();
        if (!data) return null;

        if (data.accessToken.expiresAt < Date.now()) {
            authLogger.info('PersistenceService: Found expired session in storage. Clearing...');
            await this.clearState();
            return null;
        }

        if (data.version !== DB_VERSION) {
            authLogger.info(`PersistenceService: Migrating data from v${data.version} to v${DB_VERSION}.`);
            // Lógica de migración futura aquí...
            await this.clearState(); // Por ahora, simplemente limpiar si la versión no coincide
            return null;
        }

        return data;
    }

    async clearState(): Promise<void> {
        await this.backend.clear();
    }
}

export const PersistenceService = new PersistenceServiceClass();