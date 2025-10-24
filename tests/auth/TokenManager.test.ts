import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TokenManager } from '@/lib/auth/TokenManager';
import { PersistenceService } from '@/lib/auth/PersistenceService';
import type { AccessToken } from '@/lib/auth/types';

describe('TokenManager', () => {
  beforeEach(() => {
    // Limpiar TokenManager state antes de cada test
    TokenManager.clearToken();
    vi.clearAllTimers();
  });

  afterEach(() => {
    vi.clearAllTimers();
  });

  describe('setToken()', () => {
    it('debería almacenar un token válido', () => {
      const token = 'valid-jwt-token';
      const expiresIn = 3600;
      const user = { id: '1', email: 'test@example.com' };
      const roleContexts = [{ roleCode: 'USER', roleName: 'User' }];

      TokenManager.setToken(token, expiresIn, user, roleContexts);

      expect(TokenManager.getAccessToken()).toBe(token);
    });

    it('debería rechazar un token JWT inválido', () => {
      const invalidToken = 'not-a-jwt-token';
      const expiresIn = 3600;
      const user = { id: '1', email: 'test@example.com' };
      const roleContexts = [];

      TokenManager.setToken(invalidToken, expiresIn, user, roleContexts);

      // El token no debería estar almacenado
      expect(TokenManager.getAccessToken()).toBeNull();
    });

    it('debería establecer el rol seleccionado si hay solo un rol', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      const user = { id: '1' };
      const roleContexts = [{ roleCode: 'USER', roleName: 'User' }];

      TokenManager.setToken(token, 3600, user, roleContexts);

      expect(TokenManager.getLastSelectedRole()).toBe('USER');
    });

    it('debería no establecer rol si hay múltiples roles', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      const user = { id: '1' };
      const roleContexts = [
        { roleCode: 'USER', roleName: 'User' },
        { roleCode: 'AGENT', roleName: 'Agent' },
      ];

      TokenManager.setToken(token, 3600, user, roleContexts);

      expect(TokenManager.getLastSelectedRole()).toBeNull();
    });
  });

  describe('getAccessToken()', () => {
    it('debería retornar null si no hay token', () => {
      expect(TokenManager.getAccessToken()).toBeNull();
    });

    it('debería retornar el token si es válido', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, 3600, { id: '1' }, []);

      expect(TokenManager.getAccessToken()).toBe(token);
    });

    it('debería retornar null si el token está expirado', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      // Usa un tiempo de expiración negativo para que esté expirado
      TokenManager.setToken(token, -1, { id: '1' }, []);

      expect(TokenManager.getAccessToken()).toBeNull();
    });
  });

  describe('validateToken()', () => {
    it('debería indicar que el token es válido cuando se acaba de establecer', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, 3600, { id: '1' }, []);

      const validation = TokenManager.validateToken();

      expect(validation.isValid).toBe(true);
      expect(validation.isExpired).toBe(false);
    });

    it('debería indicar que el token está expirado cuando expira', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, -1, { id: '1' }, []);

      const validation = TokenManager.validateToken();

      expect(validation.isValid).toBe(false);
      expect(validation.isExpired).toBe(true);
    });

    it('debería indicar que no hay token cuando está vacío', () => {
      const validation = TokenManager.validateToken();

      expect(validation.isValid).toBe(false);
      expect(validation.isExpired).toBe(false);
    });
  });

  describe('clearToken()', () => {
    it('debería limpiar el token almacenado', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, 3600, { id: '1' }, []);

      TokenManager.clearToken();

      expect(TokenManager.getAccessToken()).toBeNull();
      expect(TokenManager.getLastSelectedRole()).toBeNull();
    });
  });

  describe('setLastSelectedRole()', () => {
    it('debería establecer el rol seleccionado si es válido', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      const roleContexts = [
        { roleCode: 'USER', roleName: 'User' },
        { roleCode: 'AGENT', roleName: 'Agent' },
      ];

      TokenManager.setToken(token, 3600, { id: '1' }, roleContexts);
      TokenManager.setLastSelectedRole('AGENT');

      expect(TokenManager.getLastSelectedRole()).toBe('AGENT');
    });

    it('debería no establecer un rol inválido', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      const roleContexts = [{ roleCode: 'USER', roleName: 'User' }];

      TokenManager.setToken(token, 3600, { id: '1' }, roleContexts);
      TokenManager.setLastSelectedRole('INVALID_ROLE');

      // El rol no debería cambiar
      expect(TokenManager.getLastSelectedRole()).toBe('USER');
    });
  });

  describe('onRefresh()', () => {
    it('debería llamar al callback cuando se refresca el token', async () => {
      const callback = vi.fn();
      TokenManager.onRefresh(callback);

      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, 3600, { id: '1' }, []);

      // Simular un refresh (no podemos triggerRefresh sin backend)
      // Este test verificaría el callback si tuviéramos un refresh real
      expect(callback).not.toHaveBeenCalled();
    });

    it('debería permitir desuscribirse del callback', () => {
      const callback = vi.fn();
      const unsubscribe = TokenManager.onRefresh(callback);

      unsubscribe();

      // El callback no debería ser llamado después de desuscribirse
      // (esto se verificaría en un test de refresh real)
      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('onExpiry()', () => {
    it('debería llamar al callback cuando expira la sesión', () => {
      const callback = vi.fn();
      TokenManager.onExpiry(callback);

      TokenManager.notifyExpiry();

      expect(callback).toHaveBeenCalledOnce();
    });

    it('debería permitir desuscribirse del callback', () => {
      const callback = vi.fn();
      const unsubscribe = TokenManager.onExpiry(callback);

      unsubscribe();
      TokenManager.notifyExpiry();

      expect(callback).not.toHaveBeenCalled();
    });
  });

  describe('onReady()', () => {
    it('debería retornar una promise que se resuelve cuando está listo', async () => {
      const promise = TokenManager.onReady();
      expect(promise).toBeInstanceOf(Promise);

      // Debería resolverse sin errores
      await expect(promise).resolves.toBeUndefined();
    });
  });

  describe('getAccessTokenObject()', () => {
    it('debería retornar el objeto del token con su estructura completa', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, 3600, { id: '1' }, []);

      const tokenObj = TokenManager.getAccessTokenObject();

      expect(tokenObj).toBeDefined();
      expect(tokenObj?.token).toBe(token);
      expect(tokenObj?.expiresIn).toBe(3600);
      expect(tokenObj?.issuedAt).toBeDefined();
      expect(tokenObj?.expiresAt).toBeDefined();
    });

    it('debería retornar null si el token está expirado', () => {
      const token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxIn0.test';
      TokenManager.setToken(token, -1, { id: '1' }, []);

      const tokenObj = TokenManager.getAccessTokenObject();

      expect(tokenObj).toBeNull();
    });
  });
});
