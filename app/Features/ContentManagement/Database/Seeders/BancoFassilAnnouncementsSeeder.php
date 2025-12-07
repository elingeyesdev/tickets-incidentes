<?php

namespace App\Features\ContentManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Banco Fassil Announcements Seeder
 *
 * Creates sample announcements for Banco Fassil S.A. company with:
 * - Various types (MAINTENANCE, INCIDENT, NEWS, ALERT)
 * - Different statuses (PUBLISHED, DRAFT, SCHEDULED, ARCHIVED)
 * - Dates from early 2025 to November 2025
 * - Realistic banking sector announcements
 */
class BancoFassilAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Find Banco Fassil company
        $company = Company::where('name', 'Banco Fassil S.A.')->first();

        if (!$company) {
            $this->command->error('Banco Fassil S.A. company not found. Please run RealBolivianCompaniesSeeder first.');
            return;
        }

        // Find company admin
        $admin = User::where('email', 'fernando.mendoza@fassil.com.bo')->first();

        if (!$admin) {
            $this->command->error('Banco Fassil company admin not found.');
            return;
        }

        $this->command->info('Creating announcements for Banco Fassil...');

        // ===== PUBLISHED ANNOUNCEMENTS =====

        // January - NEWS: New Digital Platform
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Plataforma Digital Rediseñada - Bienvenida 2025',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Banco Fassil presenta su nueva plataforma digital completamente rediseñada con interfaz mejorada y mayor velocidad. Accede desde cualquier dispositivo con mejor experiencia de usuario y seguridad avanzada.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Nueva plataforma digital con interfaz mejorada',
                'call_to_action' => [
                    'text' => 'Acceder a la plataforma',
                    'url' => 'https://www.fassil.com.bo/banca-digital',
                ],
            ],
            'published_at' => '2025-01-06 09:30:00',
            ]
        );

        // February - MAINTENANCE: System Upgrade
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Mantenimiento Programado - Actualización de Sistemas Críticos',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se realizará actualización de nuestros sistemas de procesamiento de transacciones. Durante este período, las transferencias internacionales podrían experimentar demoras de hasta 2 horas.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => '2025-02-08T22:00:00Z',
                'scheduled_end' => '2025-02-09T04:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Transferencias Internacionales', 'Pagos de Servicios'],
                'actual_start' => '2025-02-08T22:00:00Z',
                'actual_end' => '2025-02-09T03:45:00Z',
            ],
            'published_at' => '2025-02-03 10:00:00',
            ]
        );

        // March - ALERT: Regulatory Update
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Cambios Regulatorios - Actualización de Datos de Clientes',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Según nuevas regulaciones de la Autoridad de Supervisión del Sistema Financiero (ASFI), todos los clientes deben actualizar su información personal y verificar sus datos de contacto antes del 31 de marzo.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'regulatory',
                'message' => 'Actualice su información antes del 31 de marzo',
                'action_required' => true,
                'action_description' => 'Ingrese a Mi Perfil > Datos Personales y verifique su información',
                'started_at' => '2025-03-01T00:00:00Z',
                'ended_at' => '2025-03-31T23:59:59Z',
            ],
            'published_at' => '2025-03-01 08:00:00',
            ]
        );

        // April - NEWS: New Credit Product
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Lanzamiento: Crédito Rápido para PYMES',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Banco Fassil lanza su nuevo producto de crédito rápido especialmente diseñado para pequeñas y medianas empresas. Proceso de aprobación en 24 horas con tasas competitivas y sin trámites excesivos.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Nuevo producto de crédito rápido para PYMES',
                'call_to_action' => [
                    'text' => 'Solicitar crédito',
                    'url' => 'https://www.fassil.com.bo/credito-pymes',
                ],
            ],
            'published_at' => '2025-04-10 10:00:00',
            ]
        );

        // May - INCIDENT (Resolved): Service Interruption
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Incidente Resuelto - Interrupción Temporal de Servicios',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se presentó una interrupción temporal en nuestros servicios de banca móvil causada por una falla en nuestro proveedor de internet. El servicio ha sido completamente restaurado.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => true,
                'started_at' => '2025-05-14T15:30:00Z',
                'ended_at' => '2025-05-14T17:15:00Z',
                'resolved_at' => '2025-05-14T17:15:00Z',
                'resolution_content' => 'Se resolvió la falla del proveedor de conectividad. Se implementaron enlaces redundantes para prevenir futuros incidentes.',
                'affected_services' => ['Banca Móvil', 'Consulta de Saldos'],
            ],
            'published_at' => '2025-05-14 15:30:00',
            ]
        );

        // June - NEWS: Cash Rewards Program
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Programa de Cashback Ampliado - Junio 2025',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Hemos ampliado nuestro programa de cashback a más comercios afiliados. Disfruta de reembolsos de hasta el 5% en compras con tarjeta de débito en establecimientos participantes.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'promotion',
                'target_audience' => ['users'],
                'summary' => 'Programa de cashback ampliado a más comercios',
                'call_to_action' => [
                    'text' => 'Ver comercios participantes',
                    'url' => 'https://www.fassil.com.bo/cashback-comercios',
                ],
            ],
            'published_at' => '2025-06-05 09:00:00',
            ]
        );

        // July - MAINTENANCE: Certificate Update
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Actualización de Certificados de Seguridad',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se realizará una actualización de los certificados de seguridad SSL/TLS de nuestras plataformas. No se requiere acción de los clientes, pero la conexión será temporal.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-07-12T02:00:00Z',
                'scheduled_end' => '2025-07-12T04:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Banca Digital', 'App Móvil'],
                'actual_start' => '2025-07-12T02:00:00Z',
                'actual_end' => '2025-07-12T03:30:00Z',
            ],
            'published_at' => '2025-07-09 10:00:00',
            ]
        );

        // August - NEWS: Investment Webinar Series
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Series de Webinars - Educación Financiera',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Banco Fassil inicia una serie de webinars gratuitos sobre inversión, planificación financiera y ahorro. Expertos del sector compartirán estrategias para mejorar tu situación económica.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'educational',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Serie de webinars gratuitos sobre finanzas',
                'call_to_action' => [
                    'text' => 'Registrarse al webinar',
                    'url' => 'https://www.fassil.com.bo/webinars',
                ],
            ],
            'published_at' => '2025-08-15 08:30:00',
            ]
        );

        // September - ALERT: Fraudulent Activity Warning
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Alerta de Seguridad - Actividad Fraudulenta Detectada',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se han detectado intentos de fraude dirigidos a clientes de nuestro banco. NO compartas jamás tu contraseña o PIN. Banco Fassil NUNCA solicita datos sensibles por correo o teléfono.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'alert_type' => 'security',
                'message' => 'Protégete contra intentos de fraude',
                'action_required' => true,
                'action_description' => 'Si recibiste mensajes sospechosos, reporta inmediatamente a soporte@fassil.com.bo',
                'started_at' => '2025-09-01T00:00:00Z',
                'ended_at' => '2025-09-30T23:59:59Z',
            ],
            'published_at' => '2025-09-03 10:30:00',
            ]
        );

        // October - NEWS: Anniversary Celebration
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Celebración de Aniversario - Ofertas Especiales',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Banco Fassil celebra años de servicio a la comunidad boliviana con ofertas especiales: tasas preferenciales en créditos, comisiones reducidas y bonificaciones en tarjetas de crédito.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'celebration',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Ofertas especiales por aniversario del banco',
                'call_to_action' => [
                    'text' => 'Ver ofertas',
                    'url' => 'https://www.fassil.com.bo/aniversario-2025',
                ],
            ],
            'published_at' => '2025-10-08 09:00:00',
            ]
        );

        // November - INCIDENT (Active): System Performance
        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Investigando - Lentitud en Procesamiento de Transacciones',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Nuestro equipo técnico está investigando reportes de lentitud en el procesamiento de transacciones internacionales. Se han asignado recursos adicionales para mejorar el desempeño.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => false,
                'started_at' => '2025-11-20T10:00:00Z',
                'affected_services' => ['Transferencias Internacionales', 'Consulta de Transacciones'],
            ],
            'published_at' => '2025-11-20 10:15:00',
            ]
        );

        // ===== DRAFT ANNOUNCEMENTS =====

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Nueva Tarjeta de Débito Premium - En Desarrollo',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Próximamente lanzaremos una nueva tarjeta de débito premium con beneficios exclusivos: seguros incluidos, acceso a lounges VIP y programa de puntos mejorado.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users'],
                'summary' => 'Nueva tarjeta de débito premium próximamente',
            ],
            'published_at' => null,
            ]
        );

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Integración con Billetera Digital - Próximamente',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Estamos integrando nuestros servicios con la billetera digital del gobierno. Pronto podrás pagar servicios e impuestos desde Banco Fassil.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Integración con billetera digital del gobierno',
            ],
            'published_at' => null,
            ]
        );

        // ===== SCHEDULED ANNOUNCEMENTS =====

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Cierre por Fiestas - Diciembre 2025',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Banco Fassil estará cerrado durante los días festivos de diciembre. La banca digital y app móvil seguirán operativas. Servicio de atención al cliente será limitado.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Horarios especiales en diciembre',
                'scheduled_for' => '2025-11-28T08:00:00Z',
            ],
            'published_at' => null,
            ]
        );

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Mantenimiento Anual de Infraestructura - Diciembre',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se realizará mantenimiento anual de nuestros servidores y centros de datos. Se espera que algunos servicios no críticos estén fuera de servicio durante este período.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'urgency' => 'LOW',
                'scheduled_start' => '2025-12-18T00:00:00Z',
                'scheduled_end' => '2025-12-20T08:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Servicio de Reportes', 'Extracciones de Información'],
                'scheduled_for' => '2025-12-10T09:00:00Z',
            ],
            'published_at' => null,
            ]
        );

        // ===== ARCHIVED ANNOUNCEMENTS =====

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Mantenimiento Completado - Enero 2025',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'El mantenimiento de nuestros sistemas de banca digital se completó exitosamente. Todos los servicios están operativos y disponibles.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-01-02T02:00:00Z',
                'scheduled_end' => '2025-01-02T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Banca Digital'],
                'actual_start' => '2025-01-02T02:00:00Z',
                'actual_end' => '2025-01-02T05:30:00Z',
            ],
            'published_at' => '2024-12-30 10:00:00',
            ]
        );

        // [IDEMPOTENCY] Use firstOrCreate to prevent duplicate announcements
        Announcement::firstOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Incidente Resuelto - Error en Depósitos de Nómina',
            ],
            [
            'author_id' => $admin->id,
            'content' => 'Se detectó y corrigió un error que afectaba el procesamiento de depósitos de nómina en algunas empresas. Todos los depósitos pendientes fueron procesados correctamente.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => true,
                'started_at' => '2025-04-22T08:00:00Z',
                'ended_at' => '2025-04-22T11:00:00Z',
                'resolved_at' => '2025-04-22T11:00:00Z',
                'resolution_content' => 'Se identificó un error en el módulo de procesamiento de nóminas. Se corrigió y se ejecutó nuevamente el procesamiento de transacciones pendientes.',
                'affected_services' => ['Depósitos de Nómina', 'Procesamiento Masivo'],
            ],
            'published_at' => '2025-04-22 08:00:00',
            ]
        );

        $this->command->info('Banco Fassil announcements created successfully!');
        $this->command->info('- 11 Published announcements');
        $this->command->info('- 2 Draft announcements');
        $this->command->info('- 2 Scheduled announcements');
        $this->command->info('- 2 Archived announcements');
    }
}
