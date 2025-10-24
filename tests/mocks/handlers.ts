import { http, HttpResponse } from 'msw';
import type { AccessToken } from '@/lib/auth/types';

// Mock tokens
export const MOCK_ACCESS_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiI1IiwiZW1haWwiOiJ0ZXN0QGV4YW1wbGUuY29tIiwiaWF0IjoxNjI5NzkyNDAwLCJleHAiOjE2Mjk3OTYwMDB9.test';
export const MOCK_EXPIRES_IN = 3600; // 1 hour

export const MOCK_USER = {
  id: '5',
  email: 'test@example.com',
  displayName: 'Test User',
  emailVerified: true,
  onboardingCompletedAt: '2024-10-20T10:00:00Z',
  roleContexts: [
    {
      roleCode: 'USER',
      roleName: 'User',
      dashboardPath: '/dashboard/user',
      company: null,
    },
  ],
};

export const MOCK_USER_MULTI_ROLE = {
  ...MOCK_USER,
  roleContexts: [
    {
      roleCode: 'USER',
      roleName: 'User',
      dashboardPath: '/dashboard/user',
      company: null,
    },
    {
      roleCode: 'AGENT',
      roleName: 'Agent',
      dashboardPath: '/dashboard/agent',
      company: null,
    },
  ],
};

export const MOCK_USER_UNVERIFIED = {
  ...MOCK_USER,
  emailVerified: false,
};

export const MOCK_USER_ONBOARDING_INCOMPLETE = {
  ...MOCK_USER,
  onboardingCompletedAt: null,
};

// MSW Handlers
export const handlers = [
  // ============================================
  // LOGIN
  // ============================================
  http.post('http://localhost:8000/graphql', async ({ request }) => {
    const body = await request.json() as any;

    if (body.operationName === 'Login') {
      const variables = body.variables as any;

      // Valid login
      if (variables.input.email === 'test@example.com' && variables.input.password === 'password123') {
        return HttpResponse.json({
          data: {
            login: {
              accessToken: MOCK_ACCESS_TOKEN,
              expiresIn: MOCK_EXPIRES_IN,
              user: MOCK_USER,
            },
          },
        });
      }

      // Invalid credentials
      return HttpResponse.json({
        errors: [
          {
            message: 'Credenciales inválidas',
            extensions: { code: 'INVALID_CREDENTIALS' },
          },
        ],
      }, { status: 401 });
    }

    return HttpResponse.json({ data: null });
  }),

  // ============================================
  // LOGOUT
  // ============================================
  http.post('http://localhost:8000/graphql', async ({ request }) => {
    const body = await request.json() as any;

    if (body.operationName === 'Logout') {
      return HttpResponse.json({
        data: {
          logout: true,
        },
      });
    }

    return HttpResponse.json({ data: null });
  }),

  // ============================================
  // AUTH STATUS
  // ============================================
  http.post('http://localhost:8000/graphql', async ({ request }) => {
    const body = await request.json() as any;

    if (body.operationName === 'AuthStatus') {
      // Check if user is authenticated (has valid token)
      const authHeader = request.headers.get('authorization');
      if (!authHeader || !authHeader.startsWith('Bearer ')) {
        return HttpResponse.json({
          data: {
            authStatus: {
              isAuthenticated: false,
              user: null,
            },
          },
        });
      }

      return HttpResponse.json({
        data: {
          authStatus: {
            isAuthenticated: true,
            user: MOCK_USER,
            currentSession: {
              sessionId: 'session-123',
              deviceName: 'Test Browser',
              ipAddress: '127.0.0.1',
              lastUsedAt: new Date().toISOString(),
              expiresAt: new Date(Date.now() + 3600000).toISOString(),
              isCurrent: true,
            },
            tokenInfo: {
              expiresIn: MOCK_EXPIRES_IN,
              issuedAt: new Date().toISOString(),
              tokenType: 'Bearer',
            },
          },
        },
      });
    }

    return HttpResponse.json({ data: null });
  }),

  // ============================================
  // REFRESH TOKEN (REST endpoint)
  // ============================================
  http.post('http://localhost:8000/api/auth/refresh', ({ request }) => {
    // Check if refresh token is present (httpOnly cookie)
    const cookies = request.headers.get('cookie') || '';

    // Valid refresh
    if (cookies.includes('refresh_token=valid')) {
      return HttpResponse.json(
        {
          accessToken: MOCK_ACCESS_TOKEN,
          expiresIn: MOCK_EXPIRES_IN,
        },
        { status: 200 }
      );
    }

    // Invalid/expired refresh token
    return HttpResponse.json(
      {
        error: 'INVALID_REFRESH_TOKEN',
        message: 'Refresh token is invalid or expired',
      },
      { status: 401 }
    );
  }),

  // ============================================
  // HEARTBEAT
  // ============================================
  http.post('http://localhost:8000/graphql', async ({ request }) => {
    const body = await request.json() as any;

    if (body.operationName === 'Heartbeat') {
      const authHeader = request.headers.get('authorization');
      if (!authHeader) {
        return HttpResponse.json({
          data: {
            authStatus: {
              isAuthenticated: false,
            },
          },
        });
      }

      return HttpResponse.json({
        data: {
          authStatus: {
            isAuthenticated: true,
          },
        },
      });
    }

    return HttpResponse.json({ data: null });
  }),

  // ============================================
  // VERIFY EMAIL
  // ============================================
  http.post('http://localhost:8000/graphql', async ({ request }) => {
    const body = await request.json() as any;

    if (body.operationName === 'VerifyEmail') {
      const variables = body.variables as any;

      if (variables.token === 'valid-token') {
        return HttpResponse.json({
          data: {
            verifyEmail: {
              success: true,
              message: 'Email verificado exitosamente',
              canResend: false,
              resendAvailableAt: null,
            },
          },
        });
      }

      return HttpResponse.json({
        errors: [
          {
            message: 'Token de verificación inválido',
            extensions: { code: 'INVALID_TOKEN' },
          },
        ],
      }, { status: 400 });
    }

    return HttpResponse.json({ data: null });
  }),
];
