import { describe, it, expect, beforeEach } from 'vitest';
import { TokenManager } from '@/lib/auth';

const validJWT = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.dozjgNryP4J3jVmNHl0w5N_XgL0n3I9PlFUP9THsR8U';

describe('TokenManager', () => {
  beforeEach(() => {
    localStorage.clear();
    TokenManager.clearToken();
  });

  it('should set and get token', () => {
    TokenManager.setToken(validJWT, 3600, null, []);
    expect(TokenManager.getAccessToken()).toBe(validJWT);
  });

  it('should clear token', () => {
    TokenManager.setToken(validJWT, 3600, null, []);
    TokenManager.clearToken();
    expect(TokenManager.getAccessToken()).toBeNull();
  });

  it('should validate token', () => {
    TokenManager.setToken(validJWT, 3600, null, []);
    const validation = TokenManager.validateToken();
    expect(validation).not.toBeNull();
  });
});
