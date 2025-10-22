/* eslint-disable */
import { TypedDocumentNode as DocumentNode } from '@graphql-typed-document-node/core';
export type Maybe<T> = T | null;
export type InputMaybe<T> = T | null | undefined;
export type Exact<T extends { [key: string]: unknown }> = { [K in keyof T]: T[K] };
export type MakeOptional<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]?: Maybe<T[SubKey]> };
export type MakeMaybe<T, K extends keyof T> = Omit<T, K> & { [SubKey in K]: Maybe<T[SubKey]> };
export type MakeEmpty<T extends { [key: string]: unknown }, K extends keyof T> = { [_ in K]?: never };
export type Incremental<T> = T | { [P in keyof T]?: P extends ' $fragmentName' | '__typename' ? T[P] : never };
/** All built-in and custom scalars, mapped to their actual values */
export type Scalars = {
  ID: { input: string; output: string; }
  String: { input: string; output: string; }
  Boolean: { input: boolean; output: boolean; }
  Int: { input: number; output: number; }
  Float: { input: number; output: number; }
  /** Solo fecha en formato Y-m-d */
  Date: { input: any; output: any; }
  /** Fecha y hora en formato ISO 8601 (Zulu) */
  DateTime: { input: string; output: string; }
  /** Email address */
  Email: { input: string; output: string; }
  /** Color hexadecimal en formato #RRGGBB o #RGB */
  HexColor: { input: any; output: any; }
  /** Arbitrary JSON object */
  JSON: { input: any; output: any; }
  /** Número de teléfono en formato internacional E.164 */
  PhoneNumber: { input: any; output: any; }
  /** Valid URL */
  URL: { input: any; output: any; }
  /** UUID v4 para identificadores únicos del sistema */
  UUID: { input: string; output: string; }
};

/** Información de versión del API */
export type ApiVersion = {
  __typename?: 'ApiVersion';
  /** Entorno de ejecución (local, staging, production) */
  environment: Scalars['String']['output'];
  /** Versión de Laravel framework */
  laravel: Scalars['String']['output'];
  /** Versión de Lighthouse GraphQL */
  lighthouse: Scalars['String']['output'];
  /** Timestamp de la respuesta */
  timestamp: Scalars['DateTime']['output'];
  /** Versión del API (semver) */
  version: Scalars['String']['output'];
};

/**
 * Input para asignar rol a usuario
 * Validación crítica: roles que requieren empresa deben tener companyId
 */
export type AssignRoleInput = {
  /**
   * ID de empresa (requerido SOLO si el rol requiere empresa)
   * - AGENT y COMPANY_ADMIN: requieren companyId
   * - USER y PLATFORM_ADMIN: NO requieren companyId (debe ser null)
   */
  companyId?: InputMaybe<Scalars['UUID']['input']>;
  /** Código del rol a asignar */
  roleCode: RoleCode;
  /** ID del usuario */
  userId: Scalars['UUID']['input'];
};

/**
 * Response de login/register
 * Contiene SOLO lo necesario para autenticación inicial
 *
 * SEGURIDAD (V10.0+):
 * El campo refreshToken devuelve un mensaje informativo.
 * El refresh token real se establece en una cookie HttpOnly por seguridad.
 */
export type AuthPayload = {
  __typename?: 'AuthPayload';
  /** Token de acceso JWT (corta duración: 15-60 min) */
  accessToken: Scalars['String']['output'];
  /** Tiempo de expiración del access token en segundos */
  expiresIn: Scalars['Int']['output'];
  /** Timestamp del login */
  loginTimestamp: Scalars['DateTime']['output'];
  /**
   * NOTA: Este campo devuelve un mensaje informativo.
   * El refresh token real se establece en cookie HttpOnly por seguridad.
   * No es accesible desde JavaScript (previene XSS).
   */
  refreshToken: Scalars['String']['output'];
  /** ID de sesión único */
  sessionId: Scalars['String']['output'];
  /** Tipo de token (siempre "Bearer") */
  tokenType: Scalars['String']['output'];
  /** Información básica del usuario autenticado */
  user: UserAuthInfo;
};

/** Tipo de proveedor de autenticación */
export enum AuthProvider {
  /** Autenticación con Facebook OAuth (futuro) */
  Facebook = 'FACEBOOK',
  /** Autenticación con Google OAuth */
  Google = 'GOOGLE',
  /** Autenticación local con email/password */
  Local = 'LOCAL'
}

/** Estado de autenticación del usuario actual */
export type AuthStatus = {
  __typename?: 'AuthStatus';
  /** Sesión actual (null si no autenticado) */
  currentSession?: Maybe<SessionInfo>;
  /** Si está autenticado */
  isAuthenticated: Scalars['Boolean']['output'];
  /** Info del token actual */
  tokenInfo?: Maybe<TokenInfo>;
  /** Información del usuario (null si no autenticado) */
  user?: Maybe<UserAuthInfo>;
};

/**
 * Type COMPLETO de empresa - SIN loops infinitos
 * CASO DE USO: Query company($id) para detalle completo
 */
export type Company = Node & Timestamped & {
  __typename?: 'Company';
  activeAgentsCount: Scalars['Int']['output'];
  adminEmail: Scalars['Email']['output'];
  adminId: Scalars['UUID']['output'];
  adminName: Scalars['String']['output'];
  businessHours: Scalars['JSON']['output'];
  companyCode: Scalars['String']['output'];
  contactAddress?: Maybe<Scalars['String']['output']>;
  contactCity?: Maybe<Scalars['String']['output']>;
  contactCountry?: Maybe<Scalars['String']['output']>;
  createdAt: Scalars['DateTime']['output'];
  followersCount: Scalars['Int']['output'];
  id: Scalars['UUID']['output'];
  isFollowedByMe?: Maybe<Scalars['Boolean']['output']>;
  legalName?: Maybe<Scalars['String']['output']>;
  legalRepresentative?: Maybe<Scalars['String']['output']>;
  logoUrl?: Maybe<Scalars['URL']['output']>;
  name: Scalars['String']['output'];
  openTicketsCount: Scalars['Int']['output'];
  phone?: Maybe<Scalars['PhoneNumber']['output']>;
  primaryColor: Scalars['HexColor']['output'];
  secondaryColor: Scalars['HexColor']['output'];
  status: CompanyStatus;
  supportEmail?: Maybe<Scalars['Email']['output']>;
  taxId?: Maybe<Scalars['String']['output']>;
  timezone: Scalars['String']['output'];
  totalTicketsCount: Scalars['Int']['output'];
  totalUsersCount: Scalars['Int']['output'];
  updatedAt: Scalars['DateTime']['output'];
  website?: Maybe<Scalars['URL']['output']>;
};

export type CompanyBrandingInput = {
  faviconUrl?: InputMaybe<Scalars['URL']['input']>;
  logoUrl?: InputMaybe<Scalars['URL']['input']>;
  primaryColor?: InputMaybe<Scalars['HexColor']['input']>;
  secondaryColor?: InputMaybe<Scalars['HexColor']['input']>;
};

export type CompanyConfigInput = {
  businessHours?: InputMaybe<Scalars['JSON']['input']>;
  settings?: InputMaybe<Scalars['JSON']['input']>;
  timezone?: InputMaybe<Scalars['String']['input']>;
};

/** Wrapper para contexto EXPLORE */
export type CompanyExploreList = {
  __typename?: 'CompanyExploreList';
  hasNextPage: Scalars['Boolean']['output'];
  items: Array<CompanyForFollowing>;
  totalCount: Scalars['Int']['output'];
};

/** Filtros para query companies */
export type CompanyFilters = {
  country?: InputMaybe<Scalars['String']['input']>;
  followedByMe?: InputMaybe<Scalars['Boolean']['input']>;
  hasActiveTickets?: InputMaybe<Scalars['Boolean']['input']>;
  industry?: InputMaybe<Scalars['String']['input']>;
  status?: InputMaybe<CompanyStatus>;
};

/**
 * Información de empresa seguida CON contexto de seguimiento
 * CASO DE USO: myFollowedCompanies query
 */
export type CompanyFollowInfo = {
  __typename?: 'CompanyFollowInfo';
  company: CompanyMinimal;
  followedAt: Scalars['DateTime']['output'];
  hasUnreadAnnouncements: Scalars['Boolean']['output'];
  id: Scalars['UUID']['output'];
  lastTicketCreatedAt?: Maybe<Scalars['DateTime']['output']>;
  myTicketsCount: Scalars['Int']['output'];
};

/** Resultado de seguir una empresa */
export type CompanyFollowResult = {
  __typename?: 'CompanyFollowResult';
  company: CompanyMinimal;
  followedAt: Scalars['DateTime']['output'];
  message: Scalars['String']['output'];
  success: Scalars['Boolean']['output'];
};

/**
 * Type para EXPLORAR empresas y seguir
 * CASO DE USO: Página "Explorar Empresas", selector de onboarding
 */
export type CompanyForFollowing = {
  __typename?: 'CompanyForFollowing';
  /** Ciudad */
  city?: Maybe<Scalars['String']['output']>;
  /** Código único */
  companyCode: Scalars['String']['output'];
  /** País */
  country?: Maybe<Scalars['String']['output']>;
  /** Descripción breve del negocio */
  description?: Maybe<Scalars['String']['output']>;
  /** Total de seguidores (social proof) */
  followersCount: Scalars['Int']['output'];
  /** ID único */
  id: Scalars['UUID']['output'];
  /** Sector o industria */
  industry?: Maybe<Scalars['String']['output']>;
  /** Si yo la estoy siguiendo */
  isFollowedByMe: Scalars['Boolean']['output'];
  /** Logo */
  logoUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre comercial */
  name: Scalars['String']['output'];
  /** Color primario para branding */
  primaryColor?: Maybe<Scalars['HexColor']['output']>;
};

/** Wrapper para contexto MANAGEMENT/ANALYTICS */
export type CompanyFullList = {
  __typename?: 'CompanyFullList';
  hasNextPage: Scalars['Boolean']['output'];
  items: Array<Company>;
  totalCount: Scalars['Int']['output'];
};

/**
 * Información mínima de empresa para referencias y selectores
 * Usar en relaciones para evitar loops infinitos
 * NO incluye listas de usuarios, agentes o tickets
 */
export type CompanyMinimal = {
  __typename?: 'CompanyMinimal';
  /** Código único legible de la empresa */
  companyCode: Scalars['String']['output'];
  /** ID único de la empresa */
  id: Scalars['UUID']['output'];
  /** URL del logo de la empresa */
  logoUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre comercial de la empresa */
  name: Scalars['String']['output'];
};

/** Wrapper para contexto MINIMAL */
export type CompanyMinimalList = {
  __typename?: 'CompanyMinimalList';
  hasNextPage: Scalars['Boolean']['output'];
  items: Array<CompanyMinimal>;
  totalCount: Scalars['Int']['output'];
};

/**
 * Contexto de uso para la query 'companies'
 * Determina qué campos se devuelven para optimizar performance
 */
export enum CompanyQueryContext {
  /** Para dashboards: + estadísticas agregadas */
  Analytics = 'ANALYTICS',
  /** Para explorar y seguir: + description, industry, location, followers (11 campos) */
  Explore = 'EXPLORE',
  /** Para administración: todos los campos públicos */
  Management = 'MANAGEMENT',
  /** Para selectores simples: id, code, name, logo (4 campos) */
  Minimal = 'MINIMAL'
}

/** Resultado inteligente basado en el contexto solicitado */
export type CompanyQueryResult = CompanyExploreList | CompanyFullList | CompanyMinimalList;

/** Solicitud de empresa pendiente de aprobación */
export type CompanyRequest = Node & Timestamped & {
  __typename?: 'CompanyRequest';
  adminEmail: Scalars['Email']['output'];
  businessDescription: Scalars['String']['output'];
  companyName: Scalars['String']['output'];
  createdAt: Scalars['DateTime']['output'];
  id: Scalars['UUID']['output'];
  requestCode: Scalars['String']['output'];
  status: CompanyRequestStatus;
  updatedAt: Scalars['DateTime']['output'];
};

/** Input para solicitar empresa */
export type CompanyRequestInput = {
  adminEmail: Scalars['Email']['input'];
  businessDescription: Scalars['String']['input'];
  companyName: Scalars['String']['input'];
  contactAddress?: InputMaybe<Scalars['String']['input']>;
  contactCity?: InputMaybe<Scalars['String']['input']>;
  contactCountry?: InputMaybe<Scalars['String']['input']>;
  industryType: Scalars['String']['input'];
  taxId?: InputMaybe<Scalars['String']['input']>;
  website?: InputMaybe<Scalars['URL']['input']>;
};

/** Estados de solicitud de creación de empresa */
export enum CompanyRequestStatus {
  /** Solicitud aprobada y empresa creada */
  Approved = 'APPROVED',
  /** Solicitud pendiente de revisión */
  Pending = 'PENDING',
  /** Solicitud rechazada */
  Rejected = 'REJECTED'
}

/** Estados posibles de una empresa */
export enum CompanyStatus {
  /** Empresa activa operando normalmente */
  Active = 'ACTIVE',
  /** Empresa suspendida temporalmente */
  Suspended = 'SUSPENDED'
}

export type ContactInfoInput = {
  address?: InputMaybe<Scalars['String']['input']>;
  city?: InputMaybe<Scalars['String']['input']>;
  country?: InputMaybe<Scalars['String']['input']>;
  legalRepresentative?: InputMaybe<Scalars['String']['input']>;
  postalCode?: InputMaybe<Scalars['String']['input']>;
  state?: InputMaybe<Scalars['String']['input']>;
  taxId?: InputMaybe<Scalars['String']['input']>;
};

/** Input para crear empresa directamente (admin) */
export type CreateCompanyInput = {
  adminUserId: Scalars['UUID']['input'];
  contactInfo?: InputMaybe<ContactInfoInput>;
  initialConfig?: InputMaybe<CompanyConfigInput>;
  legalName?: InputMaybe<Scalars['String']['input']>;
  name: Scalars['String']['input'];
  phone?: InputMaybe<Scalars['PhoneNumber']['input']>;
  supportEmail?: InputMaybe<Scalars['Email']['input']>;
  website?: InputMaybe<Scalars['URL']['input']>;
};

/**
 * Rango de fechas para filtros
 * Usado en queries que requieren filtrado por rangos temporales
 */
export type DateRange = {
  /** Fecha de inicio (inclusive) */
  from: Scalars['DateTime']['input'];
  /** Fecha de fin (inclusive) */
  to: Scalars['DateTime']['input'];
};

/** Resultado de verificación de email */
export type EmailVerificationResult = {
  __typename?: 'EmailVerificationResult';
  /** Si puede reenviar (en caso de fallo) */
  canResend: Scalars['Boolean']['output'];
  /** Mensaje descriptivo */
  message: Scalars['String']['output'];
  /** Cuándo puede reenviar */
  resendAvailableAt?: Maybe<Scalars['DateTime']['output']>;
  /** Si la operación fue exitosa */
  success: Scalars['Boolean']['output'];
};

/** Estado de verificación de email */
export type EmailVerificationStatus = {
  __typename?: 'EmailVerificationStatus';
  /** Intentos de reenvío restantes */
  attemptsRemaining: Scalars['Int']['output'];
  /** Si puede reenviar verificación */
  canResend: Scalars['Boolean']['output'];
  /** Email del usuario */
  email: Scalars['Email']['output'];
  /** Si el email está verificado */
  isVerified: Scalars['Boolean']['output'];
  /** Cuándo puede reenviar nuevamente */
  resendAvailableAt?: Maybe<Scalars['DateTime']['output']>;
  /** Cuándo se envió la última verificación */
  verificationSentAt?: Maybe<Scalars['DateTime']['output']>;
};

/** Input para login con Google OAuth */
export type GoogleLoginInput = {
  /** Nombre del dispositivo */
  deviceName?: InputMaybe<Scalars['String']['input']>;
  /** Token de Google OAuth */
  googleToken: Scalars['String']['input'];
};

/** Respuesta de health check para estado de servicios */
export type HealthCheck = {
  __typename?: 'HealthCheck';
  /** Detalles adicionales del estado */
  details?: Maybe<Scalars['String']['output']>;
  /** Nombre del servicio */
  service: Scalars['String']['output'];
  /** Estado del servicio (healthy, degraded, down) */
  status: Scalars['String']['output'];
  /** Timestamp del check */
  timestamp: Scalars['DateTime']['output'];
};

/** Input para login con email/password */
export type LoginInput = {
  /** Nombre del dispositivo (para tracking) */
  deviceName?: InputMaybe<Scalars['String']['input']>;
  /** Email del usuario */
  email: Scalars['Email']['input'];
  /** Contraseña */
  password: Scalars['String']['input'];
  /** Recordar sesión por más tiempo */
  rememberMe?: InputMaybe<Scalars['Boolean']['input']>;
};

/** Response de marcar onboarding como completado */
export type MarkOnboardingCompletedResponse = {
  __typename?: 'MarkOnboardingCompletedResponse';
  /** Mensaje descriptivo */
  message: Scalars['String']['output'];
  /** Si la operación fue exitosa */
  success: Scalars['Boolean']['output'];
  /** Usuario actualizado con onboarding completado */
  user?: Maybe<UserAuthInfo>;
};

/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type Mutation = {
  __typename?: 'Mutation';
  /**
   * Reactiva un usuario suspendido
   * Retorna SOLO userId y status actualizado
   * Solo accesible por platform admins
   */
  activateUser: UserStatusPayload;
  approveCompanyRequest: Company;
  /**
   * Asigna un rol a un usuario de manera INTELIGENTE:
   * - Si el rol existe inactivo: lo REACTIVA
   * - Si el rol no existe: lo CREA
   * - Si el rol requiere empresa, companyId es obligatorio
   * - Retorna información completa del resultado
   */
  assignRole: UserRoleResult;
  /**
   * Confirma reset de contraseña con token válido
   * Establece nueva contraseña e invalida todas las sesiones
   */
  confirmPasswordReset: PasswordResetResult;
  createCompany: Company;
  /**
   * Elimina lógicamente un usuario (soft delete)
   * Anonimiza datos sensibles según GDPR
   * Solo accesible por platform admins
   */
  deleteUser: Scalars['Boolean']['output'];
  followCompany: CompanyFollowResult;
  /**
   * Autentica usuario con email y contraseña
   * Retorna tokens y roles disponibles para selector
   *
   * SEGURIDAD (V10.0+): El refresh token se establece en cookie HttpOnly
   * y no se devuelve en el JSON response por seguridad.
   */
  login: AuthPayload;
  /**
   * Autentica usuario con token de Google OAuth
   * Crea cuenta automáticamente si no existe
   */
  loginWithGoogle: AuthPayload;
  /**
   * Cierra sesión del usuario
   * everywhere=false: solo sesión actual
   * everywhere=true: todas las sesiones
   */
  logout: Scalars['Boolean']['output'];
  /**
   * Marca el onboarding como completado para el usuario autenticado.
   * Se llama automáticamente al finalizar ConfigurePreferences (paso 2).
   * Establece onboarding_completed_at timestamp en la BD.
   */
  markOnboardingCompleted: MarkOnboardingCompletedResponse;
  /**
   * ⚠️ DEPRECADO: Usar endpoint REST POST /auth/refresh (más seguro con HttpOnly cookies)
   *
   * Genera nuevo access token usando refresh token válido
   * Invalida refresh token anterior por seguridad
   *
   * SEGURIDAD MEJORADA (V10.0+):
   * - El refresh token se lee desde cookie HttpOnly (más seguro)
   * - También soporta Header X-Refresh-Token (compatibilidad)
   * - Argumento refreshToken en body mantiene compatibilidad con Apollo Studio
   *
   * RECOMENDACIÓN: Usar endpoint REST POST /auth/refresh en su lugar
   * El endpoint REST lee el refresh token exclusivamente desde cookie HttpOnly,
   * lo cual es más seguro que enviarlo en el body o headers.
   *
   * NO requiere access token - solo refresh token (el access token puede estar expirado)
   */
  refreshToken: RefreshPayload;
  /**
   * Registra un nuevo usuario en el sistema
   * Genera tokens automáticamente tras registro exitoso
   *
   * SEGURIDAD (V10.0+): El refresh token se establece en cookie HttpOnly
   * y no se devuelve en el JSON response por seguridad.
   */
  register: AuthPayload;
  rejectCompanyRequest: Scalars['Boolean']['output'];
  /**
   * Remueve un rol de un usuario (soft delete - reversible)
   * Establece isActive = false, registra revokedAt y reason
   * Para reactivarlo, usar assignRole con los mismos parámetros
   */
  removeRole: Scalars['Boolean']['output'];
  requestCompany: CompanyRequest;
  /**
   * Reenvía email de verificación al usuario autenticado
   * Rate limiting: 3 intentos cada 5 minutos
   */
  resendVerification: EmailVerificationResult;
  /**
   * Solicita reset de contraseña
   * Envía email con token. Siempre retorna true por seguridad
   */
  resetPassword: Scalars['Boolean']['output'];
  /**
   * Revoca una sesión específica de otro dispositivo
   * No puede revocar su propia sesión actual
   */
  revokeOtherSession: Scalars['Boolean']['output'];
  /**
   * Suspende temporalmente un usuario
   * Invalida todas sus sesiones activas
   * Retorna SOLO userId y status actualizado
   * Solo accesible por platform admins
   */
  suspendUser: UserStatusPayload;
  unfollowCompany: Scalars['Boolean']['output'];
  updateCompany: Company;
  /**
   * Actualiza PREFERENCIAS de interfaz y notificaciones (theme, language, notifications)
   * Retorna SOLO las preferencias actualizadas
   * Rate limit: 50 actualizaciones por hora (más frecuente que perfil)
   */
  updateMyPreferences: PreferencesUpdatePayload;
  /**
   * Actualiza DATOS PERSONALES del perfil (firstName, lastName, phone, avatar)
   * Retorna SOLO el perfil actualizado (sin roleContexts, tickets, etc.)
   * Rate limit: 30 actualizaciones por hora
   */
  updateMyProfile: ProfileUpdatePayload;
  /**
   * Verifica email del usuario usando token
   * El token es suficiente para identificar al usuario (estándar industria)
   */
  verifyEmail: EmailVerificationResult;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationActivateUserArgs = {
  id: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationApproveCompanyRequestArgs = {
  requestId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationAssignRoleArgs = {
  input: AssignRoleInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationConfirmPasswordResetArgs = {
  input: PasswordResetInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationCreateCompanyArgs = {
  input: CreateCompanyInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationDeleteUserArgs = {
  id: Scalars['UUID']['input'];
  reason?: InputMaybe<Scalars['String']['input']>;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationFollowCompanyArgs = {
  companyId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationLoginArgs = {
  input: LoginInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationLoginWithGoogleArgs = {
  input: GoogleLoginInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationLogoutArgs = {
  everywhere?: InputMaybe<Scalars['Boolean']['input']>;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRefreshTokenArgs = {
  refreshToken?: InputMaybe<Scalars['String']['input']>;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRegisterArgs = {
  input: RegisterInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRejectCompanyRequestArgs = {
  reason: Scalars['String']['input'];
  requestId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRemoveRoleArgs = {
  reason?: InputMaybe<Scalars['String']['input']>;
  roleId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRequestCompanyArgs = {
  input: CompanyRequestInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationResetPasswordArgs = {
  email: Scalars['Email']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationRevokeOtherSessionArgs = {
  sessionId: Scalars['String']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationSuspendUserArgs = {
  id: Scalars['UUID']['input'];
  reason?: InputMaybe<Scalars['String']['input']>;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationUnfollowCompanyArgs = {
  companyId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationUpdateCompanyArgs = {
  id: Scalars['UUID']['input'];
  input: UpdateCompanyInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationUpdateMyPreferencesArgs = {
  input: PreferencesInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationUpdateMyProfileArgs = {
  input: UpdateProfileInput;
};


/**
 * Indica los campos disponibles en el nivel superior de mutations
 * Features extienden este tipo con sus propias mutations
 */
export type MutationVerifyEmailArgs = {
  token: Scalars['String']['input'];
};

/**
 * Entidad con identificador único UUID
 * Implementar en todos los tipos que representan entidades persistentes
 */
export type Node = {
  /** ID único UUID v4 de la entidad */
  id: Scalars['UUID']['output'];
};

/** Allows ordering a list of records. */
export type OrderByClause = {
  /** The column that is used for ordering. */
  column: Scalars['String']['input'];
  /** The direction that is used for ordering. */
  order: SortOrder;
};

/** Aggregate functions when ordering by a relation without specifying a column. */
export enum OrderByRelationAggregateFunction {
  /** Amount of items. */
  Count = 'COUNT'
}

/** Aggregate functions when ordering by a relation that may specify a column. */
export enum OrderByRelationWithColumnAggregateFunction {
  /** Average. */
  Avg = 'AVG',
  /** Amount of items. */
  Count = 'COUNT',
  /** Maximum. */
  Max = 'MAX',
  /** Minimum. */
  Min = 'MIN',
  /** Sum. */
  Sum = 'SUM'
}

/**
 * Información estándar de paginación
 * Usado en todos los tipos paginados del sistema
 * Compatible con estándar Relay Cursor Connections
 */
export type PaginatorInfo = {
  __typename?: 'PaginatorInfo';
  /** Número de página actual (comienza en 1) */
  currentPage: Scalars['Int']['output'];
  /** Primera posición del cursor en la página actual */
  firstItem?: Maybe<Scalars['Int']['output']>;
  /** Si existen más páginas después de la actual */
  hasMorePages: Scalars['Boolean']['output'];
  /** Última posición del cursor en la página actual */
  lastItem?: Maybe<Scalars['Int']['output']>;
  /** Número de la última página disponible */
  lastPage: Scalars['Int']['output'];
  /** Cantidad de registros por página */
  perPage: Scalars['Int']['output'];
  /** Número total de registros disponibles */
  total: Scalars['Int']['output'];
};

/** Input para confirmar reset de contraseña */
export type PasswordResetInput = {
  /** Nueva contraseña */
  password: Scalars['String']['input'];
  /** Confirmación de nueva contraseña */
  passwordConfirmation: Scalars['String']['input'];
  /** Token de reset */
  token: Scalars['String']['input'];
};

/** Resultado de reset de contraseña */
export type PasswordResetResult = {
  __typename?: 'PasswordResetResult';
  /** Mensaje descriptivo */
  message: Scalars['String']['output'];
  /** Si la operación fue exitosa */
  success: Scalars['Boolean']['output'];
  /** Usuario (solo si exitoso) */
  user?: Maybe<UserMinimal>;
};

/** Estado de un token de reset de contraseña */
export type PasswordResetStatus = {
  __typename?: 'PasswordResetStatus';
  /** Intentos restantes */
  attemptsRemaining: Scalars['Int']['output'];
  /** Si puede resetear la contraseña */
  canReset: Scalars['Boolean']['output'];
  /** Email asociado (parcialmente oculto) */
  email?: Maybe<Scalars['String']['output']>;
  /** Cuándo expira el token */
  expiresAt?: Maybe<Scalars['DateTime']['output']>;
  /** Si el token es válido */
  isValid: Scalars['Boolean']['output'];
};

/**
 * Input para actualizar preferencias de interfaz y notificaciones
 * Separado de UpdateProfileInput (patrón profesional)
 */
export type PreferencesInput = {
  language?: InputMaybe<Scalars['String']['input']>;
  notificationsTickets?: InputMaybe<Scalars['Boolean']['input']>;
  pushWebNotifications?: InputMaybe<Scalars['Boolean']['input']>;
  theme?: InputMaybe<Scalars['String']['input']>;
  timezone?: InputMaybe<Scalars['String']['input']>;
};

/**
 * Resultado de updateMyPreferences
 * Retorna SOLO las preferencias actualizadas
 */
export type PreferencesUpdatePayload = {
  __typename?: 'PreferencesUpdatePayload';
  /** Preferencias actualizadas */
  preferences: UserPreferences;
  /** Timestamp de la actualización */
  updatedAt: Scalars['DateTime']['output'];
  /** ID del usuario */
  userId: Scalars['UUID']['output'];
};

/**
 * Resultado de updateMyProfile
 * Retorna SOLO el perfil actualizado (sin roleContexts, tickets, etc.)
 */
export type ProfileUpdatePayload = {
  __typename?: 'ProfileUpdatePayload';
  /** Perfil actualizado con datos personales */
  profile: UserProfile;
  /** Timestamp de la actualización */
  updatedAt: Scalars['DateTime']['output'];
  /** ID del usuario */
  userId: Scalars['UUID']['output'];
};

/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type Query = {
  __typename?: 'Query';
  /**
   * Estado completo de autenticación del usuario actual
   * Incluye información de sesión y token
   */
  authStatus: AuthStatus;
  /**
   * Roles disponibles en el sistema
   * Lista estática de roles con sus descripciones
   * Solo accesible por PLATFORM_ADMIN y COMPANY_ADMIN (validado en resolver)
   * Cache de 1 hora para performance (cache privada por usuario)
   */
  availableRoles: Array<RoleInfo>;
  /**
   * Query principal con contextos inteligentes
   * USA ESTO para: selectores, exploradores, listas
   */
  companies: CompanyQueryResult;
  /** Detalle completo de una empresa específica */
  company?: Maybe<Company>;
  /** Panel administrativo: solicitudes pendientes */
  companyRequests: Array<CompanyRequest>;
  /** Estado de verificación de email del usuario actual */
  emailVerificationStatus: EmailVerificationStatus;
  /** Chequeo de salud de servicios del sistema */
  health: Array<HealthCheck>;
  /** Verificación rápida de seguimiento */
  isFollowingCompany: Scalars['Boolean']['output'];
  /**
   * Usuario autenticado con información completa
   * Incluye perfil, roles activos (roleContexts) y estadísticas
   */
  me: User;
  /** Mis empresas seguidas con estadísticas personales */
  myFollowedCompanies: Array<CompanyFollowInfo>;
  /**
   * Perfil completo del usuario autenticado
   * Para páginas de configuración y edición
   */
  myProfile: UserProfile;
  /**
   * Lista de sesiones activas del usuario autenticado
   * Para gestión de dispositivos conectados
   */
  mySessions: Array<SessionInfo>;
  /**
   * Valida el estado de un token de reset de contraseña
   * Verifica si el token es válido antes de mostrar formulario
   */
  passwordResetStatus: PasswordResetStatus;
  /** Simple ping-pong health check */
  ping: Scalars['String']['output'];
  /**
   * Usuario específico por ID con información COMPLETA
   * Misma estructura que 'me' para consistencia
   * Acceso según permisos del usuario autenticado
   */
  user?: Maybe<User>;
  /**
   * Lista paginada de usuarios del sistema
   * Solo accesible por administradores
   * Máximo 50 registros por página
   */
  users: UserPaginator;
  /** Información de versión del API y sistema */
  version: ApiVersion;
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryCompaniesArgs = {
  context: CompanyQueryContext;
  filters?: InputMaybe<CompanyFilters>;
  first?: InputMaybe<Scalars['Int']['input']>;
  page?: InputMaybe<Scalars['Int']['input']>;
  search?: InputMaybe<Scalars['String']['input']>;
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryCompanyArgs = {
  id: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryCompanyRequestsArgs = {
  first?: InputMaybe<Scalars['Int']['input']>;
  status?: InputMaybe<CompanyRequestStatus>;
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryIsFollowingCompanyArgs = {
  companyId: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryPasswordResetStatusArgs = {
  token: Scalars['String']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryUserArgs = {
  id: Scalars['UUID']['input'];
};


/**
 * Indica los campos disponibles en el nivel superior de queries
 * Features extienden este tipo con sus propias queries
 */
export type QueryUsersArgs = {
  filters?: InputMaybe<UserFilters>;
  first?: InputMaybe<Scalars['Int']['input']>;
  orderBy?: InputMaybe<Array<UserOrderBy>>;
  page?: InputMaybe<Scalars['Int']['input']>;
};

/**
 * Response de refresh token
 * Versión minimalista sin información de usuario
 */
export type RefreshPayload = {
  __typename?: 'RefreshPayload';
  /** Nuevo token de acceso JWT */
  accessToken: Scalars['String']['output'];
  /** Tiempo de expiración en segundos */
  expiresIn: Scalars['Int']['output'];
  /** Nuevo token de refresh */
  refreshToken: Scalars['String']['output'];
  /** Tipo de token */
  tokenType: Scalars['String']['output'];
};

/** Input para registro de nuevo usuario */
export type RegisterInput = {
  /** Acepta política de privacidad */
  acceptsPrivacyPolicy: Scalars['Boolean']['input'];
  /** Acepta términos y condiciones */
  acceptsTerms: Scalars['Boolean']['input'];
  /** Email único del usuario */
  email: Scalars['Email']['input'];
  /** Nombre del usuario */
  firstName: Scalars['String']['input'];
  /** Apellido del usuario */
  lastName: Scalars['String']['input'];
  /** Contraseña (mínimo 8 caracteres) */
  password: Scalars['String']['input'];
  /** Confirmación de contraseña */
  passwordConfirmation: Scalars['String']['input'];
};

/** Códigos de roles del sistema */
export enum RoleCode {
  /** Agente que responde tickets de una empresa */
  Agent = 'AGENT',
  /** Administrador de una empresa específica */
  CompanyAdmin = 'COMPANY_ADMIN',
  /** Administrador de toda la plataforma */
  PlatformAdmin = 'PLATFORM_ADMIN',
  /** Usuario final que crea tickets */
  User = 'USER'
}

/**
 * Información de empresa en contexto de rol
 * Usado exclusivamente en RoleContext
 */
export type RoleCompanyContext = {
  __typename?: 'RoleCompanyContext';
  /** Código único legible de la empresa */
  companyCode: Scalars['String']['output'];
  /** ID único de la empresa */
  id: Scalars['UUID']['output'];
  /** URL del logo de la empresa */
  logoUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre comercial de la empresa */
  name: Scalars['String']['output'];
};

/**
 * Información de rol para selector y permisos
 * Usado en AuthPayload.roleContexts y User.roleContexts
 * SIN over-fetching: company es opcional según rol
 */
export type RoleContext = {
  __typename?: 'RoleContext';
  /**
   * Información de empresa (presente SOLO si el rol requiere empresa)
   * Campo resuelto manualmente en cada feature
   */
  company?: Maybe<RoleCompanyContext>;
  /** Ruta del dashboard correspondiente al rol */
  dashboardPath: Scalars['String']['output'];
  /** Código del rol */
  roleCode: RoleCode;
  /** Nombre del rol */
  roleName: Scalars['String']['output'];
};

/**
 * Información de rol disponible en el sistema V10.1
 * Para listado de roles y validaciones
 * SIMPLIFICADO: Elimina permissions y priority
 */
export type RoleInfo = {
  __typename?: 'RoleInfo';
  /** Código único del rol */
  code: RoleCode;
  /** Dashboard por defecto */
  defaultDashboard: Scalars['String']['output'];
  /** Descripción completa */
  description: Scalars['String']['output'];
  /** Si es rol del sistema (no personalizado) */
  isSystemRole: Scalars['Boolean']['output'];
  /** Nombre legible */
  name: Scalars['String']['output'];
  /** Si requiere empresa asociada */
  requiresCompany: Scalars['Boolean']['output'];
};

/** Información de sesión activa */
export type SessionInfo = {
  __typename?: 'SessionInfo';
  /** Nombre del dispositivo */
  deviceName?: Maybe<Scalars['String']['output']>;
  /** Cuándo expira la sesión */
  expiresAt: Scalars['DateTime']['output'];
  /** IP de acceso */
  ipAddress?: Maybe<Scalars['String']['output']>;
  /** Si es la sesión actual */
  isCurrent: Scalars['Boolean']['output'];
  /** Último uso de la sesión */
  lastUsedAt: Scalars['DateTime']['output'];
  /** Ubicación estimada (opcional) */
  location?: Maybe<SessionLocation>;
  /** ID único de sesión */
  sessionId: Scalars['String']['output'];
  /** User agent del navegador */
  userAgent?: Maybe<Scalars['String']['output']>;
};

/** Ubicación de la sesión (estimada por IP) */
export type SessionLocation = {
  __typename?: 'SessionLocation';
  /** Ciudad */
  city?: Maybe<Scalars['String']['output']>;
  /** País */
  country?: Maybe<Scalars['String']['output']>;
};

/** Directions for ordering a list of records. */
export enum SortOrder {
  /** Sort records in ascending order. */
  Asc = 'ASC',
  /** Sort records in descending order. */
  Desc = 'DESC'
}

/**
 * Información básica de ticket (versión simplificada)
 * Usar en relaciones para evitar loops infinitos
 * NO incluye respuestas ni archivos adjuntos
 */
export type TicketBasicInfo = {
  __typename?: 'TicketBasicInfo';
  /** Fecha de creación */
  createdAt: Scalars['DateTime']['output'];
  /** ID único del ticket */
  id: Scalars['UUID']['output'];
  /** Prioridad del ticket */
  priority: TicketPriority;
  /** Estado actual del ticket */
  status: TicketStatus;
  /** Código único legible del ticket */
  ticketCode: Scalars['String']['output'];
  /** Título del ticket */
  title: Scalars['String']['output'];
};

/** Niveles de prioridad de tickets */
export enum TicketPriority {
  /** Prioridad alta - requiere atención pronto */
  High = 'HIGH',
  /** Prioridad baja - no urgente */
  Low = 'LOW',
  /** Prioridad media - atención normal */
  Medium = 'MEDIUM',
  /** Urgente - requiere atención inmediata */
  Urgent = 'URGENT'
}

/** Estados del ciclo de vida de un ticket */
export enum TicketStatus {
  /** Ticket cerrado y completado */
  Closed = 'CLOSED',
  /** Ticket recién creado, esperando asignación */
  Open = 'OPEN',
  /** Ticket en proceso, esperando respuesta del usuario */
  Pending = 'PENDING',
  /** Problema resuelto, esperando confirmación */
  Resolved = 'RESOLVED'
}

/**
 * Entidad con timestamps de auditoría
 * Implementar en tipos que requieren seguimiento temporal
 */
export type Timestamped = {
  /** Fecha y hora de creación del registro */
  createdAt: Scalars['DateTime']['output'];
  /** Fecha y hora de última actualización */
  updatedAt: Scalars['DateTime']['output'];
};

/** Información del token JWT actual */
export type TokenInfo = {
  __typename?: 'TokenInfo';
  /** Segundos hasta expiración */
  expiresIn: Scalars['Int']['output'];
  /** Cuándo fue emitido */
  issuedAt: Scalars['DateTime']['output'];
  /** Tipo de token */
  tokenType: Scalars['String']['output'];
};

/** Specify if you want to include or exclude trashed results from a query. */
export enum Trashed {
  /** Only return trashed results. */
  Only = 'ONLY',
  /** Return both trashed and non-trashed results. */
  With = 'WITH',
  /** Only return non-trashed results. */
  Without = 'WITHOUT'
}

/** Input para actualizar empresa */
export type UpdateCompanyInput = {
  branding?: InputMaybe<CompanyBrandingInput>;
  config?: InputMaybe<CompanyConfigInput>;
  contactInfo?: InputMaybe<ContactInfoInput>;
  legalName?: InputMaybe<Scalars['String']['input']>;
  name?: InputMaybe<Scalars['String']['input']>;
  phone?: InputMaybe<Scalars['PhoneNumber']['input']>;
  supportEmail?: InputMaybe<Scalars['Email']['input']>;
  website?: InputMaybe<Scalars['URL']['input']>;
};

/**
 * Input para actualizar datos personales del perfil
 * Solo campos modificables: firstName, lastName, phoneNumber, avatarUrl
 */
export type UpdateProfileInput = {
  avatarUrl?: InputMaybe<Scalars['URL']['input']>;
  firstName?: InputMaybe<Scalars['String']['input']>;
  lastName?: InputMaybe<Scalars['String']['input']>;
  phoneNumber?: InputMaybe<Scalars['String']['input']>;
};

/**
 * Usuario completo del sistema V10.1
 * SOLO para queries: me, user(id)
 * NO usado en mutations (usan Payloads específicos)
 */
export type User = Node & Timestamped & {
  __typename?: 'User';
  authProvider: AuthProvider;
  /**
   * Rating promedio (solo para agentes con tickets resueltos)
   * Null para usuarios sin rating
   * TODO: Implementar DataLoader cuando exista feature Ratings
   */
  averageRating?: Maybe<Scalars['Float']['output']>;
  createdAt: Scalars['DateTime']['output'];
  /** Fecha de eliminación (soft delete) */
  deletedAt?: Maybe<Scalars['DateTime']['output']>;
  email: Scalars['Email']['output'];
  emailVerified: Scalars['Boolean']['output'];
  id: Scalars['UUID']['output'];
  /** Última actividad registrada en el sistema */
  lastActivityAt?: Maybe<Scalars['DateTime']['output']>;
  /** Última vez que el usuario inició sesión */
  lastLoginAt?: Maybe<Scalars['DateTime']['output']>;
  /** Timestamp cuando se completó el onboarding (null si no se ha completado) */
  onboardingCompletedAt?: Maybe<Scalars['DateTime']['output']>;
  /**
   * Perfil del usuario con información personal y preferencias
   * Usa DataLoader para prevenir N+1 queries
   */
  profile: UserProfile;
  /**
   * Total de tickets resueltos (solo agentes)
   * TODO: Implementar DataLoader cuando exista feature Ticketing
   */
  resolvedTicketsCount: Scalars['Int']['output'];
  /**
   * Contextos de roles ACTIVOS únicamente
   * Misma estructura que login/register para consistencia 100%
   * Reutiliza RoleContext de shared types
   * Usa DataLoader para prevenir N+1 queries
   */
  roleContexts: Array<RoleContext>;
  status: UserStatus;
  /**
   * Total de tickets creados por el usuario
   * TODO: Implementar DataLoader cuando exista feature Ticketing
   */
  ticketsCount: Scalars['Int']['output'];
  updatedAt: Scalars['DateTime']['output'];
  userCode: Scalars['String']['output'];
};

/**
 * Información de usuario para contexto de autenticación
 * Usado en AuthPayload y AuthStatus
 */
export type UserAuthInfo = {
  __typename?: 'UserAuthInfo';
  /** URL del avatar del usuario */
  avatarUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre para mostrar (firstName + lastName) */
  displayName: Scalars['String']['output'];
  /** Email del usuario */
  email: Scalars['Email']['output'];
  /** Email verificado */
  emailVerified: Scalars['Boolean']['output'];
  /** ID único del usuario */
  id: Scalars['UUID']['output'];
  /** Idioma preferido */
  language: Scalars['String']['output'];
  /** Onboarding completado (completar perfil + configurar preferencias) */
  onboardingCompleted: Scalars['Boolean']['output'];
  /** Timestamp cuando se completó el onboarding (null si no se ha completado) */
  onboardingCompletedAt?: Maybe<Scalars['DateTime']['output']>;
  /**
   * Contextos de roles disponibles del usuario
   * Resuelve automáticamente los roles activos con sus empresas asociadas
   * Usa DataLoader para prevenir N+1 queries
   */
  roleContexts: Array<RoleContext>;
  /** Estado actual del usuario */
  status: UserStatus;
  /** Tema de interfaz preferido */
  theme: Scalars['String']['output'];
  /** Código único legible del usuario */
  userCode: Scalars['String']['output'];
};

/**
 * Información básica de usuario (versión con status)
 * Usar en relaciones donde se necesita conocer el estado
 * NO incluye relaciones complejas ni listas anidadas
 */
export type UserBasicInfo = {
  __typename?: 'UserBasicInfo';
  /** URL del avatar del usuario */
  avatarUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre para mostrar (firstName + lastName) */
  displayName: Scalars['String']['output'];
  /** Email del usuario */
  email: Scalars['Email']['output'];
  /** ID único del usuario */
  id: Scalars['UUID']['output'];
  /** Estado actual del usuario */
  status: UserStatus;
  /** Código único legible del usuario */
  userCode: Scalars['String']['output'];
};

/** Filtros para query users */
export type UserFilters = {
  /** Filtrar por empresa (usuarios con rol en esta empresa) */
  companyId?: InputMaybe<Scalars['UUID']['input']>;
  /** Filtrar por rango de creación */
  createdBetween?: InputMaybe<DateRange>;
  /** Filtrar por email verificado */
  emailVerified?: InputMaybe<Scalars['Boolean']['input']>;
  /** Filtrar por actividad reciente (últimos 7 días) */
  recentActivity?: InputMaybe<Scalars['Boolean']['input']>;
  /** Filtrar por rol específico */
  role?: InputMaybe<RoleCode>;
  /** Búsqueda de texto en email/nombre */
  search?: InputMaybe<Scalars['String']['input']>;
  /** Filtrar por estado */
  status?: InputMaybe<UserStatus>;
};

/**
 * Type mínimo de usuario para referencias
 * Usado en assignedBy, revokedBy, y otras referencias simples
 */
export type UserMinimal = {
  __typename?: 'UserMinimal';
  /** URL del avatar del usuario */
  avatarUrl?: Maybe<Scalars['URL']['output']>;
  /** Nombre para mostrar (firstName + lastName) */
  displayName: Scalars['String']['output'];
  /** Email del usuario */
  email: Scalars['Email']['output'];
  /** ID único del usuario */
  id: Scalars['UUID']['output'];
  /** Código único legible del usuario */
  userCode: Scalars['String']['output'];
};

/**
 * Ordenamiento de usuarios
 * Reutiliza UserOrderField y SortOrder de shared enums
 */
export type UserOrderBy = {
  field: UserOrderField;
  order: SortOrder;
};

/** Campo de ordenamiento para usuarios */
export enum UserOrderField {
  /** Ordenar por fecha de creación */
  CreatedAt = 'CREATED_AT',
  /** Ordenar por email */
  Email = 'EMAIL',
  /** Ordenar por último login */
  LastLoginAt = 'LAST_LOGIN_AT',
  /** Ordenar por estado */
  Status = 'STATUS',
  /** Ordenar por cantidad de tickets */
  TicketsCount = 'TICKETS_COUNT',
  /** Ordenar por última actualización */
  UpdatedAt = 'UPDATED_AT'
}

/**
 * Paginador de usuarios
 * Reutiliza PaginatorInfo de shared types
 */
export type UserPaginator = {
  __typename?: 'UserPaginator';
  data: Array<User>;
  paginatorInfo: PaginatorInfo;
};

/**
 * Preferencias de usuario V10.1
 * Solo PREFERENCIAS de interfaz y notificaciones
 * Separado de UserProfile para claridad en mutations
 */
export type UserPreferences = {
  __typename?: 'UserPreferences';
  language: Scalars['String']['output'];
  notificationsTickets: Scalars['Boolean']['output'];
  pushWebNotifications: Scalars['Boolean']['output'];
  theme: Scalars['String']['output'];
  timezone: Scalars['String']['output'];
  updatedAt: Scalars['DateTime']['output'];
};

/**
 * Perfil de usuario V10.1
 * Solo DATOS PERSONALES (sin preferencias)
 */
export type UserProfile = {
  __typename?: 'UserProfile';
  avatarUrl?: Maybe<Scalars['URL']['output']>;
  createdAt: Scalars['DateTime']['output'];
  displayName: Scalars['String']['output'];
  firstName: Scalars['String']['output'];
  language: Scalars['String']['output'];
  lastName: Scalars['String']['output'];
  notificationsTickets: Scalars['Boolean']['output'];
  phoneNumber?: Maybe<Scalars['String']['output']>;
  pushWebNotifications: Scalars['Boolean']['output'];
  theme: Scalars['String']['output'];
  timezone: Scalars['String']['output'];
  updatedAt: Scalars['DateTime']['output'];
};

/**
 * Información de rol asignado a un usuario V10.1
 * Estructura CLARA: company estará presente según el tipo de rol
 * Reutiliza CompanyMinimal y UserMinimal de shared types
 */
export type UserRoleInfo = {
  __typename?: 'UserRoleInfo';
  /** Cuándo fue asignado el rol */
  assignedAt: Scalars['DateTime']['output'];
  /**
   * Quién asignó el rol
   * Reutiliza UserMinimal de shared types
   */
  assignedBy?: Maybe<UserMinimal>;
  /**
   * Empresa asociada al rol
   * Presente para AGENT y COMPANY_ADMIN
   * Null para USER y PLATFORM_ADMIN
   * Reutiliza CompanyMinimal de shared types
   */
  company?: Maybe<CompanyMinimal>;
  /** ID único del registro de rol */
  id: Scalars['UUID']['output'];
  /** Si el rol está activo */
  isActive: Scalars['Boolean']['output'];
  /** Código del rol */
  roleCode: RoleCode;
  /** Nombre del rol */
  roleName: Scalars['String']['output'];
};

/**
 * Resultado de asignación de rol V10.1
 * Indica si fue creado o reactivado
 */
export type UserRoleResult = {
  __typename?: 'UserRoleResult';
  /** Mensaje descriptivo del resultado ('asignado' o 'reactivado') */
  message: Scalars['String']['output'];
  /** Información del rol asignado/reactivado */
  role: UserRoleInfo;
  /** Si la operación fue exitosa */
  success: Scalars['Boolean']['output'];
};

/** Estados posibles de un usuario en el sistema */
export enum UserStatus {
  /** Usuario activo con acceso completo */
  Active = 'ACTIVE',
  /** Usuario eliminado (soft delete) */
  Deleted = 'DELETED',
  /** Usuario suspendido temporalmente */
  Suspended = 'SUSPENDED'
}

/**
 * Resultado de suspendUser/activateUser
 * Retorna SOLO userId y status actualizado
 */
export type UserStatusPayload = {
  __typename?: 'UserStatusPayload';
  /** Estado actualizado del usuario */
  status: UserStatus;
  /** Timestamp de la actualización */
  updatedAt: Scalars['DateTime']['output'];
  /** ID del usuario */
  userId: Scalars['UUID']['output'];
};

export type HeartbeatQueryVariables = Exact<{ [key: string]: never; }>;


export type HeartbeatQuery = { __typename?: 'Query', authStatus: { __typename?: 'AuthStatus', isAuthenticated: boolean } };

export type UserProfileFieldsFragment = { __typename?: 'UserProfile', firstName: string, lastName: string, displayName: string, phoneNumber?: string | null, avatarUrl?: any | null, createdAt: string, updatedAt: string } & { ' $fragmentName'?: 'UserProfileFieldsFragment' };

export type UserPreferencesFieldsFragment = { __typename?: 'UserPreferences', theme: string, language: string, timezone: string, pushWebNotifications: boolean, notificationsTickets: boolean, updatedAt: string } & { ' $fragmentName'?: 'UserPreferencesFieldsFragment' };

export type RoleContextFieldsFragment = { __typename?: 'RoleContext', roleCode: RoleCode, roleName: string, dashboardPath: string, company?: { __typename?: 'RoleCompanyContext', id: string, companyCode: string, name: string, logoUrl?: any | null } | null } & { ' $fragmentName'?: 'RoleContextFieldsFragment' };

export type UserFullFieldsFragment = { __typename?: 'User', id: string, userCode: string, email: string, emailVerified: boolean, status: UserStatus, authProvider: AuthProvider, ticketsCount: number, resolvedTicketsCount: number, averageRating?: number | null, lastLoginAt?: string | null, createdAt: string, updatedAt: string, profile: (
    { __typename?: 'UserProfile' }
    & { ' $fragmentRefs'?: { 'UserProfileFieldsFragment': UserProfileFieldsFragment } }
  ), roleContexts: Array<(
    { __typename?: 'RoleContext' }
    & { ' $fragmentRefs'?: { 'RoleContextFieldsFragment': RoleContextFieldsFragment } }
  )> } & { ' $fragmentName'?: 'UserFullFieldsFragment' };

export type UserAuthInfoFieldsFragment = { __typename?: 'UserAuthInfo', id: string, userCode: string, email: string, emailVerified: boolean, onboardingCompleted: boolean, onboardingCompletedAt?: string | null, status: UserStatus, displayName: string, avatarUrl?: any | null, theme: string, language: string, roleContexts: Array<(
    { __typename?: 'RoleContext' }
    & { ' $fragmentRefs'?: { 'RoleContextFieldsFragment': RoleContextFieldsFragment } }
  )> } & { ' $fragmentName'?: 'UserAuthInfoFieldsFragment' };

export type AuthPayloadFieldsFragment = { __typename?: 'AuthPayload', accessToken: string, refreshToken: string, tokenType: string, expiresIn: number, sessionId: string, loginTimestamp: string, user: (
    { __typename?: 'UserAuthInfo' }
    & { ' $fragmentRefs'?: { 'UserAuthInfoFieldsFragment': UserAuthInfoFieldsFragment } }
  ) } & { ' $fragmentName'?: 'AuthPayloadFieldsFragment' };

export type CompanyMinimalFieldsFragment = { __typename?: 'CompanyMinimal', id: string, companyCode: string, name: string, logoUrl?: any | null } & { ' $fragmentName'?: 'CompanyMinimalFieldsFragment' };

export type RegisterMutationVariables = Exact<{
  input: RegisterInput;
}>;


export type RegisterMutation = { __typename?: 'Mutation', register: { __typename?: 'AuthPayload', accessToken: string, expiresIn: number, user: { __typename?: 'UserAuthInfo', id: string, email: string, onboardingCompletedAt?: string | null, roleContexts: Array<{ __typename?: 'RoleContext', roleCode: RoleCode, roleName: string, dashboardPath: string }> } } };

export type LoginMutationVariables = Exact<{
  input: LoginInput;
}>;


export type LoginMutation = { __typename?: 'Mutation', login: { __typename?: 'AuthPayload', accessToken: string, expiresIn: number, user: { __typename?: 'UserAuthInfo', id: string, email: string, onboardingCompletedAt?: string | null, roleContexts: Array<{ __typename?: 'RoleContext', roleCode: RoleCode, roleName: string, dashboardPath: string }> } } };

export type LoginWithGoogleMutationVariables = Exact<{
  input: GoogleLoginInput;
}>;


export type LoginWithGoogleMutation = { __typename?: 'Mutation', loginWithGoogle: (
    { __typename?: 'AuthPayload' }
    & { ' $fragmentRefs'?: { 'AuthPayloadFieldsFragment': AuthPayloadFieldsFragment } }
  ) };

export type LogoutMutationVariables = Exact<{
  everywhere?: InputMaybe<Scalars['Boolean']['input']>;
}>;


export type LogoutMutation = { __typename?: 'Mutation', logout: boolean };

export type RefreshTokenMutationVariables = Exact<{ [key: string]: never; }>;


export type RefreshTokenMutation = { __typename?: 'Mutation', refreshToken: { __typename?: 'RefreshPayload', accessToken: string, refreshToken: string, tokenType: string, expiresIn: number } };

export type VerifyEmailMutationVariables = Exact<{
  token: Scalars['String']['input'];
}>;


export type VerifyEmailMutation = { __typename?: 'Mutation', verifyEmail: { __typename?: 'EmailVerificationResult', success: boolean, message: string, canResend: boolean, resendAvailableAt?: string | null } };

export type ResendVerificationMutationVariables = Exact<{ [key: string]: never; }>;


export type ResendVerificationMutation = { __typename?: 'Mutation', resendVerification: { __typename?: 'EmailVerificationResult', success: boolean, message: string, canResend: boolean, resendAvailableAt?: string | null } };

export type ResetPasswordMutationVariables = Exact<{
  email: Scalars['Email']['input'];
}>;


export type ResetPasswordMutation = { __typename?: 'Mutation', resetPassword: boolean };

export type ConfirmPasswordResetMutationVariables = Exact<{
  input: PasswordResetInput;
}>;


export type ConfirmPasswordResetMutation = { __typename?: 'Mutation', confirmPasswordReset: { __typename?: 'PasswordResetResult', success: boolean, message: string, user?: { __typename?: 'UserMinimal', id: string, email: string, displayName: string } | null } };

export type MarkOnboardingCompletedMutationVariables = Exact<{ [key: string]: never; }>;


export type MarkOnboardingCompletedMutation = { __typename?: 'Mutation', markOnboardingCompleted: { __typename?: 'MarkOnboardingCompletedResponse', success: boolean, message: string, user?: { __typename?: 'UserAuthInfo', id: string, userCode: string, email: string, emailVerified: boolean, onboardingCompleted: boolean, displayName: string, avatarUrl?: any | null, theme: string, language: string, roleContexts: Array<{ __typename?: 'RoleContext', roleCode: RoleCode, roleName: string, dashboardPath: string, company?: { __typename?: 'RoleCompanyContext', id: string, name: string } | null }> } | null } };

export type UpdateMyProfileMutationVariables = Exact<{
  input: UpdateProfileInput;
}>;


export type UpdateMyProfileMutation = { __typename?: 'Mutation', updateMyProfile: { __typename?: 'ProfileUpdatePayload', userId: string, updatedAt: string, profile: { __typename?: 'UserProfile', firstName: string, lastName: string, displayName: string, phoneNumber?: string | null, avatarUrl?: any | null, updatedAt: string } } };

export type AuthStatusQueryVariables = Exact<{ [key: string]: never; }>;


export type AuthStatusQuery = { __typename?: 'Query', authStatus: { __typename?: 'AuthStatus', isAuthenticated: boolean, user?: (
      { __typename?: 'UserAuthInfo' }
      & { ' $fragmentRefs'?: { 'UserAuthInfoFieldsFragment': UserAuthInfoFieldsFragment } }
    ) | null, currentSession?: { __typename?: 'SessionInfo', sessionId: string, deviceName?: string | null, ipAddress?: string | null, lastUsedAt: string, expiresAt: string, isCurrent: boolean } | null, tokenInfo?: { __typename?: 'TokenInfo', expiresIn: number, issuedAt: string, tokenType: string } | null } };

export type MySessionsQueryVariables = Exact<{ [key: string]: never; }>;


export type MySessionsQuery = { __typename?: 'Query', mySessions: Array<{ __typename?: 'SessionInfo', sessionId: string, deviceName?: string | null, ipAddress?: string | null, userAgent?: string | null, lastUsedAt: string, expiresAt: string, isCurrent: boolean, location?: { __typename?: 'SessionLocation', city?: string | null, country?: string | null } | null }> };

export type EmailVerificationStatusQueryVariables = Exact<{ [key: string]: never; }>;


export type EmailVerificationStatusQuery = { __typename?: 'Query', emailVerificationStatus: { __typename?: 'EmailVerificationStatus', isVerified: boolean, email: string, verificationSentAt?: string | null, canResend: boolean, resendAvailableAt?: string | null, attemptsRemaining: number } };

export type MeQueryVariables = Exact<{ [key: string]: never; }>;


export type MeQuery = { __typename?: 'Query', me: (
    { __typename?: 'User' }
    & { ' $fragmentRefs'?: { 'UserFullFieldsFragment': UserFullFieldsFragment } }
  ) };

export type MyProfileQueryVariables = Exact<{ [key: string]: never; }>;


export type MyProfileQuery = { __typename?: 'Query', myProfile: (
    { __typename?: 'UserProfile' }
    & { ' $fragmentRefs'?: { 'UserProfileFieldsFragment': UserProfileFieldsFragment } }
  ) };

export const UserPreferencesFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserPreferencesFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserPreferences"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"timezone"}},{"kind":"Field","name":{"kind":"Name","value":"pushWebNotifications"}},{"kind":"Field","name":{"kind":"Name","value":"notificationsTickets"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}}]} as unknown as DocumentNode<UserPreferencesFieldsFragment, unknown>;
export const UserProfileFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserProfileFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserProfile"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"firstName"}},{"kind":"Field","name":{"kind":"Name","value":"lastName"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"phoneNumber"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}}]} as unknown as DocumentNode<UserProfileFieldsFragment, unknown>;
export const RoleContextFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]} as unknown as DocumentNode<RoleContextFieldsFragment, unknown>;
export const UserFullFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserFullFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"User"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"authProvider"}},{"kind":"Field","name":{"kind":"Name","value":"profile"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserProfileFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"ticketsCount"}},{"kind":"Field","name":{"kind":"Name","value":"resolvedTicketsCount"}},{"kind":"Field","name":{"kind":"Name","value":"averageRating"}},{"kind":"Field","name":{"kind":"Name","value":"lastLoginAt"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserProfileFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserProfile"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"firstName"}},{"kind":"Field","name":{"kind":"Name","value":"lastName"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"phoneNumber"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]} as unknown as DocumentNode<UserFullFieldsFragment, unknown>;
export const UserAuthInfoFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserAuthInfoFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserAuthInfo"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompleted"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]} as unknown as DocumentNode<UserAuthInfoFieldsFragment, unknown>;
export const AuthPayloadFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"AuthPayloadFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"AuthPayload"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"accessToken"}},{"kind":"Field","name":{"kind":"Name","value":"refreshToken"}},{"kind":"Field","name":{"kind":"Name","value":"tokenType"}},{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserAuthInfoFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"sessionId"}},{"kind":"Field","name":{"kind":"Name","value":"loginTimestamp"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserAuthInfoFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserAuthInfo"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompleted"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}}]}}]} as unknown as DocumentNode<AuthPayloadFieldsFragment, unknown>;
export const CompanyMinimalFieldsFragmentDoc = {"kind":"Document","definitions":[{"kind":"FragmentDefinition","name":{"kind":"Name","value":"CompanyMinimalFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"CompanyMinimal"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}}]} as unknown as DocumentNode<CompanyMinimalFieldsFragment, unknown>;
export const HeartbeatDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"Heartbeat"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"authStatus"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"isAuthenticated"}}]}}]}}]} as unknown as DocumentNode<HeartbeatQuery, HeartbeatQueryVariables>;
export const RegisterDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"Register"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"input"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"RegisterInput"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"register"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"input"},"value":{"kind":"Variable","name":{"kind":"Name","value":"input"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"accessToken"}},{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]}}]}}]}}]} as unknown as DocumentNode<RegisterMutation, RegisterMutationVariables>;
export const LoginDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"Login"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"input"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"LoginInput"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"login"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"input"},"value":{"kind":"Variable","name":{"kind":"Name","value":"input"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"accessToken"}},{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]}}]}}]}}]} as unknown as DocumentNode<LoginMutation, LoginMutationVariables>;
export const LoginWithGoogleDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"LoginWithGoogle"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"input"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"GoogleLoginInput"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"loginWithGoogle"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"input"},"value":{"kind":"Variable","name":{"kind":"Name","value":"input"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"AuthPayloadFields"}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserAuthInfoFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserAuthInfo"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompleted"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"AuthPayloadFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"AuthPayload"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"accessToken"}},{"kind":"Field","name":{"kind":"Name","value":"refreshToken"}},{"kind":"Field","name":{"kind":"Name","value":"tokenType"}},{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserAuthInfoFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"sessionId"}},{"kind":"Field","name":{"kind":"Name","value":"loginTimestamp"}}]}}]} as unknown as DocumentNode<LoginWithGoogleMutation, LoginWithGoogleMutationVariables>;
export const LogoutDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"Logout"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"everywhere"}},"type":{"kind":"NamedType","name":{"kind":"Name","value":"Boolean"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"logout"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"everywhere"},"value":{"kind":"Variable","name":{"kind":"Name","value":"everywhere"}}}]}]}}]} as unknown as DocumentNode<LogoutMutation, LogoutMutationVariables>;
export const RefreshTokenDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"RefreshToken"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"refreshToken"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"accessToken"}},{"kind":"Field","name":{"kind":"Name","value":"refreshToken"}},{"kind":"Field","name":{"kind":"Name","value":"tokenType"}},{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}}]}}]}}]} as unknown as DocumentNode<RefreshTokenMutation, RefreshTokenMutationVariables>;
export const VerifyEmailDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"VerifyEmail"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"token"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"String"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"verifyEmail"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"token"},"value":{"kind":"Variable","name":{"kind":"Name","value":"token"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"success"}},{"kind":"Field","name":{"kind":"Name","value":"message"}},{"kind":"Field","name":{"kind":"Name","value":"canResend"}},{"kind":"Field","name":{"kind":"Name","value":"resendAvailableAt"}}]}}]}}]} as unknown as DocumentNode<VerifyEmailMutation, VerifyEmailMutationVariables>;
export const ResendVerificationDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"ResendVerification"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"resendVerification"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"success"}},{"kind":"Field","name":{"kind":"Name","value":"message"}},{"kind":"Field","name":{"kind":"Name","value":"canResend"}},{"kind":"Field","name":{"kind":"Name","value":"resendAvailableAt"}}]}}]}}]} as unknown as DocumentNode<ResendVerificationMutation, ResendVerificationMutationVariables>;
export const ResetPasswordDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"ResetPassword"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"email"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"Email"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"resetPassword"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"email"},"value":{"kind":"Variable","name":{"kind":"Name","value":"email"}}}]}]}}]} as unknown as DocumentNode<ResetPasswordMutation, ResetPasswordMutationVariables>;
export const ConfirmPasswordResetDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"ConfirmPasswordReset"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"input"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"PasswordResetInput"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"confirmPasswordReset"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"input"},"value":{"kind":"Variable","name":{"kind":"Name","value":"input"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"success"}},{"kind":"Field","name":{"kind":"Name","value":"message"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}}]}}]}}]}}]} as unknown as DocumentNode<ConfirmPasswordResetMutation, ConfirmPasswordResetMutationVariables>;
export const MarkOnboardingCompletedDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"MarkOnboardingCompleted"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"markOnboardingCompleted"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"success"}},{"kind":"Field","name":{"kind":"Name","value":"message"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompleted"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"name"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}}]}}]}}]}}]} as unknown as DocumentNode<MarkOnboardingCompletedMutation, MarkOnboardingCompletedMutationVariables>;
export const UpdateMyProfileDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"mutation","name":{"kind":"Name","value":"UpdateMyProfile"},"variableDefinitions":[{"kind":"VariableDefinition","variable":{"kind":"Variable","name":{"kind":"Name","value":"input"}},"type":{"kind":"NonNullType","type":{"kind":"NamedType","name":{"kind":"Name","value":"UpdateProfileInput"}}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"updateMyProfile"},"arguments":[{"kind":"Argument","name":{"kind":"Name","value":"input"},"value":{"kind":"Variable","name":{"kind":"Name","value":"input"}}}],"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"userId"}},{"kind":"Field","name":{"kind":"Name","value":"profile"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"firstName"}},{"kind":"Field","name":{"kind":"Name","value":"lastName"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"phoneNumber"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}}]}}]} as unknown as DocumentNode<UpdateMyProfileMutation, UpdateMyProfileMutationVariables>;
export const AuthStatusDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"AuthStatus"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"authStatus"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"isAuthenticated"}},{"kind":"Field","name":{"kind":"Name","value":"user"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserAuthInfoFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"currentSession"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"sessionId"}},{"kind":"Field","name":{"kind":"Name","value":"deviceName"}},{"kind":"Field","name":{"kind":"Name","value":"ipAddress"}},{"kind":"Field","name":{"kind":"Name","value":"lastUsedAt"}},{"kind":"Field","name":{"kind":"Name","value":"expiresAt"}},{"kind":"Field","name":{"kind":"Name","value":"isCurrent"}}]}},{"kind":"Field","name":{"kind":"Name","value":"tokenInfo"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"expiresIn"}},{"kind":"Field","name":{"kind":"Name","value":"issuedAt"}},{"kind":"Field","name":{"kind":"Name","value":"tokenType"}}]}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserAuthInfoFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserAuthInfo"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompleted"}},{"kind":"Field","name":{"kind":"Name","value":"onboardingCompletedAt"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"theme"}},{"kind":"Field","name":{"kind":"Name","value":"language"}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}}]}}]} as unknown as DocumentNode<AuthStatusQuery, AuthStatusQueryVariables>;
export const MySessionsDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"MySessions"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"mySessions"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"sessionId"}},{"kind":"Field","name":{"kind":"Name","value":"deviceName"}},{"kind":"Field","name":{"kind":"Name","value":"ipAddress"}},{"kind":"Field","name":{"kind":"Name","value":"userAgent"}},{"kind":"Field","name":{"kind":"Name","value":"lastUsedAt"}},{"kind":"Field","name":{"kind":"Name","value":"expiresAt"}},{"kind":"Field","name":{"kind":"Name","value":"isCurrent"}},{"kind":"Field","name":{"kind":"Name","value":"location"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"city"}},{"kind":"Field","name":{"kind":"Name","value":"country"}}]}}]}}]}}]} as unknown as DocumentNode<MySessionsQuery, MySessionsQueryVariables>;
export const EmailVerificationStatusDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"EmailVerificationStatus"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"emailVerificationStatus"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"isVerified"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"verificationSentAt"}},{"kind":"Field","name":{"kind":"Name","value":"canResend"}},{"kind":"Field","name":{"kind":"Name","value":"resendAvailableAt"}},{"kind":"Field","name":{"kind":"Name","value":"attemptsRemaining"}}]}}]}}]} as unknown as DocumentNode<EmailVerificationStatusQuery, EmailVerificationStatusQueryVariables>;
export const MeDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"Me"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"me"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserFullFields"}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserProfileFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserProfile"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"firstName"}},{"kind":"Field","name":{"kind":"Name","value":"lastName"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"phoneNumber"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"RoleContextFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"RoleContext"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"roleCode"}},{"kind":"Field","name":{"kind":"Name","value":"roleName"}},{"kind":"Field","name":{"kind":"Name","value":"company"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"companyCode"}},{"kind":"Field","name":{"kind":"Name","value":"name"}},{"kind":"Field","name":{"kind":"Name","value":"logoUrl"}}]}},{"kind":"Field","name":{"kind":"Name","value":"dashboardPath"}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserFullFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"User"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"id"}},{"kind":"Field","name":{"kind":"Name","value":"userCode"}},{"kind":"Field","name":{"kind":"Name","value":"email"}},{"kind":"Field","name":{"kind":"Name","value":"emailVerified"}},{"kind":"Field","name":{"kind":"Name","value":"status"}},{"kind":"Field","name":{"kind":"Name","value":"authProvider"}},{"kind":"Field","name":{"kind":"Name","value":"profile"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserProfileFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"roleContexts"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"RoleContextFields"}}]}},{"kind":"Field","name":{"kind":"Name","value":"ticketsCount"}},{"kind":"Field","name":{"kind":"Name","value":"resolvedTicketsCount"}},{"kind":"Field","name":{"kind":"Name","value":"averageRating"}},{"kind":"Field","name":{"kind":"Name","value":"lastLoginAt"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}}]} as unknown as DocumentNode<MeQuery, MeQueryVariables>;
export const MyProfileDocument = {"kind":"Document","definitions":[{"kind":"OperationDefinition","operation":"query","name":{"kind":"Name","value":"MyProfile"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"myProfile"},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"FragmentSpread","name":{"kind":"Name","value":"UserProfileFields"}}]}}]}},{"kind":"FragmentDefinition","name":{"kind":"Name","value":"UserProfileFields"},"typeCondition":{"kind":"NamedType","name":{"kind":"Name","value":"UserProfile"}},"selectionSet":{"kind":"SelectionSet","selections":[{"kind":"Field","name":{"kind":"Name","value":"firstName"}},{"kind":"Field","name":{"kind":"Name","value":"lastName"}},{"kind":"Field","name":{"kind":"Name","value":"displayName"}},{"kind":"Field","name":{"kind":"Name","value":"phoneNumber"}},{"kind":"Field","name":{"kind":"Name","value":"avatarUrl"}},{"kind":"Field","name":{"kind":"Name","value":"createdAt"}},{"kind":"Field","name":{"kind":"Name","value":"updatedAt"}}]}}]} as unknown as DocumentNode<MyProfileQuery, MyProfileQueryVariables>;