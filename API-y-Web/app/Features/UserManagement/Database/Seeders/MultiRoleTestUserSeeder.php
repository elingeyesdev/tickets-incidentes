<?php

namespace App\Features\UserManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\TicketManagement\Models\Category;
use App\Features\TicketManagement\Models\Ticket;
use App\Features\TicketManagement\Models\TicketResponse;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Multi-Role Test User Seeder
 *
 * Crea un usuario de prueba con MÚLTIPLES ROLES para testear el sistema active_role:
 *
 * Usuario: multirol@test.com (password: mklmklmkl)
 *
 * ROLES:
 * 1. PLATFORM_ADMIN - Sin company_id (acceso global)
 * 2. COMPANY_ADMIN @ Victoria Veterinaria - Gestiona la empresa
 * 3. AGENT @ PIL Andina - Agente de soporte
 * 4. USER - Sin company_id (usuario final genérico)
 *
 * DATOS ADICIONALES PARA VICTORIA VETERINARIA:
 * - 3 artículos del Help Center
 * - 5 anuncios en diferentes estados (PUBLISHED, DRAFT, SCHEDULED, ARCHIVED)
 * - 4 tickets de diferentes usuarios
 *
 * DATOS ADICIONALES PARA EL ROL USER:
 * - Tickets creados a Victoria Veterinaria y PIL Andina
 * - Sigue a Victoria Veterinaria (para ver sus announcements)
 */
class MultiRoleTestUserSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';
    private const EMAIL = 'multirol@test.com';

    private ?User $multiRoleUser = null;
    private ?Company $victoriaVet = null;
    private ?Company $pilAndina = null;

    public function run(): void
    {
        $this->command->info('🎭 Creando usuario multi-rol para testing...');

        // [IDEMPOTENCY] Verificar si ya existe
        if (User::where('email', self::EMAIL)->exists()) {
            $this->command->info('[OK] Usuario multi-rol ya existe. Saltando...');
            return;
        }

        // Cargar empresas necesarias
        $this->loadCompanies();

        if (!$this->victoriaVet || !$this->pilAndina) {
            $this->command->error('❌ Empresas requeridas no encontradas. Ejecuta los seeders de empresas primero.');
            return;
        }

        // 1. Crear usuario principal
        $this->createMultiRoleUser();

        // 2. Asignar los 4 roles
        $this->assignRoles();

        // 3. Crear contenido para Victoria Veterinaria
        $this->createVictoriaArticles();
        $this->createVictoriaAnnouncements();
        $this->createVictoriaTickets();

        // 4. Crear usuarios adicionales y tickets para el rol USER
        $this->createUserTicketsAndFollows();

        $this->command->info('');
        $this->command->info('✅ ¡Usuario multi-rol creado exitosamente!');
        $this->command->info('');
        $this->command->info('📧 Email: ' . self::EMAIL);
        $this->command->info('🔑 Password: ' . self::PASSWORD);
        $this->command->info('');
        $this->command->info('🎭 Roles disponibles:');
        $this->command->info('   • PLATFORM_ADMIN (Sin empresa)');
        $this->command->info('   • COMPANY_ADMIN @ Victoria Veterinaria');
        $this->command->info('   • AGENT @ PIL Andina');
        $this->command->info('   • USER (Sin empresa)');
    }

    private function loadCompanies(): void
    {
        $this->victoriaVet = Company::where('name', 'Victoria Veterinaria')->first();
        $this->pilAndina = Company::where('name', 'PIL Andina S.A.')->first();

        if ($this->victoriaVet) {
            $this->command->info("  ✓ Victoria Veterinaria encontrada: {$this->victoriaVet->id}");
        }
        if ($this->pilAndina) {
            $this->command->info("  ✓ PIL Andina encontrada: {$this->pilAndina->id}");
        }
    }

    private function createMultiRoleUser(): void
    {
        $this->multiRoleUser = User::create([
            'user_code' => 'USR-MULTI-ROL-TEST',
            'email' => self::EMAIL,
            'password_hash' => Hash::make(self::PASSWORD),
            'email_verified' => true,
            'email_verified_at' => now(),
            'status' => UserStatus::ACTIVE,
            'auth_provider' => 'local',
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'terms_version' => 'v2.1',
            'onboarding_completed_at' => now(),
        ]);

        $this->multiRoleUser->profile()->create([
            'first_name' => 'Usuario',
            'last_name' => 'Multi-Rol',
            'phone_number' => '+59170000000',
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        $this->command->info("  ✓ Usuario creado: {$this->multiRoleUser->email}");
    }

    private function assignRoles(): void
    {
        // 1. PLATFORM_ADMIN (sin company)
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'PLATFORM_ADMIN',
            'company_id' => null,
            'is_active' => true,
        ]);
        $this->command->info('  ✓ Rol asignado: PLATFORM_ADMIN');

        // 2. COMPANY_ADMIN @ Victoria Veterinaria
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'COMPANY_ADMIN',
            'company_id' => $this->victoriaVet->id,
            'is_active' => true,
        ]);
        $this->command->info('  ✓ Rol asignado: COMPANY_ADMIN @ Victoria Veterinaria');

        // 3. AGENT @ PIL Andina
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'AGENT',
            'company_id' => $this->pilAndina->id,
            'is_active' => true,
        ]);
        $this->command->info('  ✓ Rol asignado: AGENT @ PIL Andina');

        // 4. USER (sin company - puede seguir empresas)
        UserRole::create([
            'user_id' => $this->multiRoleUser->id,
            'role_code' => 'USER',
            'company_id' => null,
            'is_active' => true,
        ]);
        $this->command->info('  ✓ Rol asignado: USER');
    }

    private function createVictoriaArticles(): void
    {
        $this->command->info('');
        $this->command->info('📚 Creando artículos para Victoria Veterinaria...');

        // Obtener una categoría existente (las categorías de artículos son globales)
        $category = ArticleCategory::first();

        if (!$category) {
            // Crear una categoría si no existe ninguna
            $category = ArticleCategory::create([
                'code' => 'pet_care',
                'name' => 'Cuidado de Mascotas',
                'description' => 'Artículos sobre el cuidado general de mascotas',
            ]);
        }

        $articles = [
            [
                'title' => 'Guía completa de vacunación para perros',
                'excerpt' => 'Todo lo que necesitas saber sobre el calendario de vacunas caninas',
                'content' => "## Calendario de Vacunación Canina\n\n### Cachorros (6-16 semanas)\n- **6-8 semanas**: Primera dosis polivalente (moquillo, hepatitis, parvovirus)\n- **10-12 semanas**: Segunda dosis polivalente + Leptospirosis\n- **14-16 semanas**: Tercera dosis polivalente + Rabia\n\n### Refuerzos Anuales\n- Polivalente anual\n- Rabia anual\n- Tos de las perreras (según exposición)\n\n### Recomendaciones\n1. Mantén al día la libreta de vacunas\n2. No saques a tu cachorro antes de completar el esquema\n3. Consulta con tu veterinario ante cualquier reacción\n\n### Contacto\nAgenda tu cita en Victoria Veterinaria: +591 3922 1234",
                'status' => 'PUBLISHED',
            ],
            [
                'title' => 'Alimentación saludable para gatos',
                'excerpt' => 'Consejos nutricionales para mantener a tu gato sano y feliz',
                'content' => "## Nutrición Felina\n\n### Necesidades Básicas\nLos gatos son carnívoros obligados y necesitan:\n- **Proteína animal**: 40-50% de la dieta\n- **Grasas**: 20-30%\n- **Taurina**: Aminoácido esencial\n- **Agua fresca**: Siempre disponible\n\n### Alimentos Prohibidos\n- Cebolla y ajo\n- Chocolate\n- Uvas y pasas\n- Leche de vaca (muchos son intolerantes)\n\n### Frecuencia de Alimentación\n- Gatitos (hasta 6 meses): 3-4 veces al día\n- Adultos: 2 veces al día\n- Senior: Según indicación veterinaria\n\n### Consultas\nVisítanos para un plan nutricional personalizado.",
                'status' => 'PUBLISHED',
            ],
            [
                'title' => 'Señales de emergencia en mascotas',
                'excerpt' => 'Aprende a identificar cuándo tu mascota necesita atención urgente',
                'content' => "## Emergencias Veterinarias\n\n### Signos de Alerta URGENTE\n🚨 **Acude inmediatamente si observas**:\n- Dificultad respiratoria\n- Sangrado abundante\n- Convulsiones\n- Pérdida de conciencia\n- Abdomen hinchado y duro\n- Imposibilidad de orinar/defecar\n\n### Signos que Requieren Consulta en 24h\n- Vómitos o diarrea persistentes\n- Falta de apetito > 24 horas\n- Cojera severa\n- Cambios de comportamiento\n\n### Qué Hacer Mientras Llegas\n1. Mantén la calma\n2. Evita manipular mucho al animal\n3. Mantén vías respiratorias libres\n4. Controla hemorragias con presión\n\n### Emergencias 24/7\n📞 Victoria Veterinaria: +591 3922 1234",
                'status' => 'PUBLISHED',
            ],
        ];

        foreach ($articles as $articleData) {
            HelpCenterArticle::firstOrCreate(
                [
                    'company_id' => $this->victoriaVet->id,
                    'title' => $articleData['title'],
                ],
                [
                    'category_id' => $category->id,
                    'author_id' => $this->victoriaVet->admin_user_id,
                    'excerpt' => $articleData['excerpt'],
                    'content' => $articleData['content'],
                    'status' => $articleData['status'],
                    'views_count' => rand(10, 150),
                    'published_at' => now(),
                ]
            );
            $this->command->info("  ✓ Artículo: {$articleData['title']}");
        }
    }

    private function createVictoriaAnnouncements(): void
    {
        $this->command->info('');
        $this->command->info('📢 Creando anuncios para Victoria Veterinaria (diferentes estados)...');

        $announcements = [
            // PUBLISHED
            [
                'title' => '¡Nuevos servicios de peluquería canina disponibles!',
                'content' => 'A partir de este mes, Victoria Veterinaria ofrece servicios completos de grooming y peluquería para todas las razas de perros. Incluye baño, corte, limpieza de oídos y corte de uñas.',
                'type' => AnnouncementType::NEWS,
                'status' => PublicationStatus::PUBLISHED,
                'published_at' => now()->subDays(5),
                'metadata' => ['news_type' => 'feature_release', 'target_audience' => ['users']],
            ],
            // PUBLISHED - MAINTENANCE
            [
                'title' => 'Mantenimiento del sistema de citas online',
                'content' => 'El próximo sábado realizaremos mantenimiento en nuestro sistema de reservas. El servicio estará no disponible de 10:00 a 14:00.',
                'type' => AnnouncementType::MAINTENANCE,
                'status' => PublicationStatus::PUBLISHED,
                'published_at' => now()->subDays(2),
                'metadata' => [
                    'urgency' => 'MEDIUM',
                    'scheduled_start' => now()->addDays(3)->setHour(10)->toISOString(),
                    'scheduled_end' => now()->addDays(3)->setHour(14)->toISOString(),
                    'affected_services' => ['Sistema de Citas Online'],
                ],
            ],
            // DRAFT
            [
                'title' => '[BORRADOR] Promoción de vacunación febrero',
                'content' => 'Campaña de vacunación con 20% de descuento en todo febrero. Incluye vacunas múltiples y antirrábica.',
                'type' => AnnouncementType::NEWS,
                'status' => PublicationStatus::DRAFT,
                'published_at' => null,
                'metadata' => ['news_type' => 'promotion', 'discount' => '20%'],
            ],
            // SCHEDULED
            [
                'title' => 'Jornada de esterilización gratuita - Enero 2025',
                'content' => 'En colaboración con la alcaldía, realizaremos jornada de esterilización gratuita para mascotas de familias de bajos recursos. Cupos limitados.',
                'type' => AnnouncementType::NEWS,
                'status' => PublicationStatus::SCHEDULED,
                'published_at' => now()->addDays(7),
                'metadata' => ['news_type' => 'community_event', 'registration_required' => true],
            ],
            // ARCHIVED
            [
                'title' => '[ARCHIVADO] Horario especial Navidad 2024',
                'content' => 'Informamos que durante fiestas de fin de año atenderemos con horario reducido: 24 y 31 de diciembre solo emergencias.',
                'type' => AnnouncementType::ALERT,
                'status' => PublicationStatus::ARCHIVED,
                'published_at' => now()->subMonth(),
                'metadata' => ['urgency' => 'LOW', 'alert_type' => 'schedule_change'],
            ],
        ];

        foreach ($announcements as $data) {
            Announcement::firstOrCreate(
                [
                    'company_id' => $this->victoriaVet->id,
                    'title' => $data['title'],
                ],
                [
                    'author_id' => $this->victoriaVet->admin_user_id,
                    'content' => $data['content'],
                    'type' => $data['type'],
                    'status' => $data['status'],
                    'published_at' => $data['published_at'],
                    'metadata' => $data['metadata'],
                ]
            );
            $statusLabel = $data['status']->value ?? $data['status'];
            $this->command->info("  ✓ Anuncio [{$statusLabel}]: {$data['title']}");
        }
    }

    private function createVictoriaTickets(): void
    {
        $this->command->info('');
        $this->command->info('🎫 Creando tickets para Victoria Veterinaria...');

        // Crear o obtener categorías de tickets
        $categories = $this->ensureTicketCategories();

        // Crear usuarios que crearán tickets
        $ticketUsers = $this->createTicketUsers();

        $tickets = [
            [
                'user_key' => 'maria_garcia',
                'category' => 'consulta_general',
                'title' => 'Consulta sobre vacuna de mi perro',
                'description' => 'Buenos días, mi perro Max tiene 4 meses y no sé si ya puede recibir la vacuna contra la rabia. ¿Cuándo debería traerlo?',
                'priority' => 'medium',
                'status' => 'open',
            ],
            [
                'user_key' => 'carlos_mendez',
                'category' => 'emergencia',
                'title' => 'Mi gato no quiere comer desde ayer',
                'description' => 'Mi gato Michi no ha comido nada desde ayer en la noche. Está echado y no quiere moverse. Normalmente es muy activo. ¿Esto es una emergencia?',
                'priority' => 'high',
                'status' => 'pending',
                'has_response' => true,
            ],
            [
                'user_key' => 'ana_lopez',
                'category' => 'producto',
                'title' => 'Problema con el alimento que compré',
                'description' => 'Compré alimento premium para mi perro la semana pasada y el empaque vino dañado. El producto está húmedo y creo que está en mal estado.',
                'priority' => 'low',
                'status' => 'resolved',
                'has_response' => true,
            ],
            [
                'user_key' => 'roberto_paz',
                'category' => 'cita',
                'title' => 'No puedo agendar cita online',
                'description' => 'Intento hacer una cita por la página web pero siempre me da error. Ya lo intenté 3 veces con diferentes navegadores.',
                'priority' => 'medium',
                'status' => 'closed',
                'has_response' => true,
            ],
        ];

        foreach ($tickets as $ticketData) {
            $user = $ticketUsers[$ticketData['user_key']];
            $category = $categories[$ticketData['category']] ?? null;

            $ticket = Ticket::firstOrCreate(
                [
                    'company_id' => $this->victoriaVet->id,
                    'created_by_user_id' => $user->id,
                    'title' => $ticketData['title'],
                ],
                [
                    'ticket_code' => 'TKT-VV-' . strtoupper(Str::random(6)),
                    'description' => $ticketData['description'],
                    'status' => $ticketData['status'],
                    'priority' => $ticketData['priority'],
                    'category_id' => $category?->id,
                    'owner_agent_id' => null, // Sin asignar inicialmente
                ]
            );

            // Crear respuesta si corresponde
            if (!empty($ticketData['has_response']) && $ticket->wasRecentlyCreated) {
                $this->createTicketResponse($ticket, $ticketData['status']);
            }

            $this->command->info("  ✓ Ticket [{$ticketData['status']}]: {$ticketData['title']}");
        }
    }

    private function ensureTicketCategories(): array
    {
        $categoriesData = [
            'consulta_general' => ['name' => 'Consulta General', 'description' => 'Preguntas generales sobre servicios'],
            'emergencia' => ['name' => 'Emergencia', 'description' => 'Casos que requieren atención urgente'],
            'producto' => ['name' => 'Productos', 'description' => 'Consultas sobre productos vendidos'],
            'cita' => ['name' => 'Citas y Reservas', 'description' => 'Problemas con sistema de citas'],
        ];

        $categories = [];
        foreach ($categoriesData as $key => $data) {
            $categories[$key] = Category::firstOrCreate(
                [
                    'company_id' => $this->victoriaVet->id,
                    'name' => $data['name'],
                ],
                [
                    'description' => $data['description'],
                    'is_active' => true,
                ]
            );
        }

        return $categories;
    }

    private function createTicketUsers(): array
    {
        $usersData = [
            'maria_garcia' => ['first_name' => 'María', 'last_name' => 'García', 'email' => 'maria.garcia.cliente@gmail.com'],
            'carlos_mendez' => ['first_name' => 'Carlos', 'last_name' => 'Méndez', 'email' => 'carlos.mendez.cliente@gmail.com'],
            'ana_lopez' => ['first_name' => 'Ana', 'last_name' => 'López', 'email' => 'ana.lopez.cliente@gmail.com'],
            'roberto_paz' => ['first_name' => 'Roberto', 'last_name' => 'Paz', 'email' => 'roberto.paz.cliente@gmail.com'],
        ];

        $users = [];
        foreach ($usersData as $key => $data) {
            $user = User::where('email', $data['email'])->first();

            if (!$user) {
                $user = User::create([
                    'user_code' => 'USR-' . strtoupper(Str::random(8)),
                    'email' => $data['email'],
                    'password_hash' => Hash::make(self::PASSWORD),
                    'email_verified' => true,
                    'email_verified_at' => now(),
                    'status' => UserStatus::ACTIVE,
                    'auth_provider' => 'local',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now()->subDays(rand(30, 90)),
                    'terms_version' => 'v2.1',
                    'onboarding_completed_at' => now()->subDays(rand(30, 90)),
                ]);

                $user->profile()->create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'phone_number' => '+591' . rand(70000000, 79999999),
                    'theme' => 'light',
                    'language' => 'es',
                    'timezone' => 'America/La_Paz',
                ]);

                // Asignar rol USER para Victoria Veterinaria
                UserRole::create([
                    'user_id' => $user->id,
                    'role_code' => 'USER',
                    'company_id' => $this->victoriaVet->id,
                    'is_active' => true,
                ]);
            }

            $users[$key] = $user;
        }

        return $users;
    }

    private function createTicketResponse(Ticket $ticket, string $status): void
    {
        $responses = [
            'pending' => 'Gracias por contactarnos. Tu caso está siendo revisado por uno de nuestros veterinarios. Te responderemos a la brevedad con una recomendación.',
            'resolved' => 'Lamentamos el inconveniente con el producto. Hemos procesado el reembolso correspondiente. Puedes pasar por nuestra tienda con tu factura para recoger un producto nuevo sin costo adicional.',
            'closed' => 'El problema fue solucionado. El sistema de citas online ya está funcionando correctamente. Gracias por reportar el inconveniente.',
        ];

        if (isset($responses[$status])) {
            // Usar un agente de Victoria Veterinaria para la respuesta
            $agent = User::where('email', 'ana.lopez@victoriavet.bo')->first();

            if ($agent) {
                TicketResponse::create([
                    'ticket_id' => $ticket->id,
                    'author_id' => $agent->id,
                    'author_type' => 'agent',
                    'content' => $responses[$status],
                ]);
            }
        }
    }

    private function createUserTicketsAndFollows(): void
    {
        $this->command->info('');
        $this->command->info('👤 Configurando datos para el rol USER del usuario multi-rol...');

        // El usuario multi-rol sigue a Victoria Veterinaria (para ver sus announcements como USER)
        DB::table('business.user_company_followers')->insertOrIgnore([
            'user_id' => $this->multiRoleUser->id,
            'company_id' => $this->victoriaVet->id,
            'followed_at' => now(),
        ]);
        $this->command->info('  ✓ Ahora sigue a: Victoria Veterinaria');

        // También sigue a PIL Andina
        DB::table('business.user_company_followers')->insertOrIgnore([
            'user_id' => $this->multiRoleUser->id,
            'company_id' => $this->pilAndina->id,
            'followed_at' => now(),
        ]);
        $this->command->info('  ✓ Ahora sigue a: PIL Andina');

        // Crear tickets como USER hacia las empresas que sigue
        $this->createUserRoleTickets();
    }

    private function createUserRoleTickets(): void
    {
        // Ticket a Victoria Veterinaria
        $victoriaCategory = Category::where('company_id', $this->victoriaVet->id)->first();

        Ticket::firstOrCreate(
            [
                'company_id' => $this->victoriaVet->id,
                'created_by_user_id' => $this->multiRoleUser->id,
                'title' => 'Consulta sobre baño para mi mascota',
            ],
            [
                'ticket_code' => 'TKT-VV-MULTI-001',
                'description' => 'Hola, quisiera saber si hacen baños para perros de raza grande (Golden Retriever). ¿Cuál es el precio y necesito cita previa?',
                'status' => 'open',
                'priority' => 'low',
                'category_id' => $victoriaCategory?->id,
            ]
        );
        $this->command->info('  ✓ Ticket creado a Victoria Veterinaria (como USER)');

        // Ticket a PIL Andina
        $pilCategory = Category::where('company_id', $this->pilAndina->id)->first();

        Ticket::firstOrCreate(
            [
                'company_id' => $this->pilAndina->id,
                'created_by_user_id' => $this->multiRoleUser->id,
                'title' => 'Consulta sobre leche deslactosada',
            ],
            [
                'ticket_code' => 'TKT-PIL-MULTI-001',
                'description' => 'Buenas tardes, compré leche PIL deslactosada y tiene un sabor diferente al usual. ¿Cambiaron la fórmula? El lote es: LT2024-1234.',
                'status' => 'pending',
                'priority' => 'medium',
                'category_id' => $pilCategory?->id,
            ]
        );
        $this->command->info('  ✓ Ticket creado a PIL Andina (como USER)');
    }
}
