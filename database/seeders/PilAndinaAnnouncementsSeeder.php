<?php

namespace Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\ContentManagement\Models\Announcement;
use App\Features\ContentManagement\Enums\AnnouncementType;
use App\Features\ContentManagement\Enums\PublicationStatus;
use App\Features\UserManagement\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * PIL Andina Announcements Seeder
 *
 * Creates sample announcements for PIL Andina company with:
 * - Various types (MAINTENANCE, INCIDENT, NEWS, ALERT)
 * - Different statuses (PUBLISHED, DRAFT, SCHEDULED, ARCHIVED)
 * - Dates distributed throughout 2025
 */
class PilAndinaAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Find PIL Andina company
        $company = Company::where('name', 'PIL Andina S.A.')->first();

        if (!$company) {
            $this->command->error('PIL Andina S.A. company not found. Please run RealBolivianCompaniesSeeder first.');
            return;
        }

        // Find company admin
        $admin = User::where('email', 'javier.rodriguez@pilandina.com.bo')->first();

        if (!$admin) {
            $this->command->error('PIL Andina company admin not found.');
            return;
        }

        $this->command->info('Creating announcements for PIL Andina...');

        // ===== PUBLISHED ANNOUNCEMENTS (distributed through 2025) =====

        // January - NEWS: New Year Update
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Bienvenida al Nuevo Año 2025',
            'content' => 'El equipo de PIL Andina les desea un próspero año nuevo. Este año traerá grandes novedades para nuestros clientes y colaboradores. Estamos comprometidos con la mejora continua de nuestros servicios y productos.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Mensaje de año nuevo y expectativas para 2025',
            ],
            'published_at' => '2025-01-02 08:00:00',
        ]);

        // February - MAINTENANCE: System Upgrade
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Programado - Actualización de Servidores',
            'content' => 'Se realizará una actualización de nuestros servidores principales para mejorar el rendimiento del sistema. Durante este período, algunos servicios podrían experimentar intermitencias.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-02-15T02:00:00Z',
                'scheduled_end' => '2025-02-15T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Portal Web', 'API de Pedidos'],
                'actual_start' => '2025-02-15T02:00:00Z',
                'actual_end' => '2025-02-15T05:30:00Z',
            ],
            'published_at' => '2025-02-10 10:00:00',
        ]);

        // March - INCIDENT (Resolved): Database Issue
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Problemas de Conectividad',
            'content' => 'Se detectaron problemas de conectividad que afectaron el acceso al sistema de pedidos. El equipo técnico identificó y resolvió el problema relacionado con la base de datos.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => true,
                'started_at' => '2025-03-05T14:30:00Z',
                'ended_at' => '2025-03-05T16:45:00Z',
                'resolved_at' => '2025-03-05T16:45:00Z',
                'resolution_content' => 'Se identificó un problema con las conexiones de base de datos. Se aumentó el pool de conexiones y se optimizaron las consultas problemáticas.',
                'affected_services' => ['Sistema de Pedidos', 'Inventario'],
            ],
            'published_at' => '2025-03-05 14:30:00',
        ]);

        // April - NEWS: New Product Launch
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Lanzamiento: Nueva Línea de Productos Deslactosados',
            'content' => 'PIL Andina se complace en anunciar el lanzamiento de nuestra nueva línea de productos deslactosados. Esta nueva gama incluye leche, yogur y quesos especialmente formulados para personas con intolerancia a la lactosa.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Nueva línea de productos deslactosados disponible',
                'call_to_action' => [
                    'text' => 'Ver catálogo completo',
                    'url' => 'https://pilandina.com.bo/productos/deslactosados',
                ],
            ],
            'published_at' => '2025-04-15 09:00:00',
        ]);

        // May - ALERT: Security Update
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Actualización de Seguridad Requerida',
            'content' => 'Hemos implementado nuevas medidas de seguridad en nuestro sistema. Todos los usuarios deben actualizar sus contraseñas antes del 31 de mayo para mantener el acceso a sus cuentas.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'security',
                'message' => 'Actualice su contraseña antes del 31 de mayo',
                'action_required' => true,
                'action_description' => 'Ingrese a Configuración > Seguridad y cambie su contraseña',
                'started_at' => '2025-05-01T00:00:00Z',
                'ended_at' => '2025-05-31T23:59:59Z',
            ],
            'published_at' => '2025-05-01 08:00:00',
        ]);

        // June - MAINTENANCE: Database Migration
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Migración de Base de Datos Programada',
            'content' => 'Se realizará una migración completa de nuestra base de datos a una infraestructura más robusta. Este proceso mejorará significativamente los tiempos de respuesta del sistema.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'scheduled_start' => '2025-06-20T00:00:00Z',
                'scheduled_end' => '2025-06-20T08:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Todos los servicios'],
                'actual_start' => '2025-06-20T00:00:00Z',
                'actual_end' => '2025-06-20T07:30:00Z',
            ],
            'published_at' => '2025-06-15 10:00:00',
        ]);

        // July - NEWS: Policy Update
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Actualización de Políticas de Privacidad',
            'content' => 'Hemos actualizado nuestras políticas de privacidad para cumplir con las nuevas regulaciones de protección de datos. Les invitamos a revisar los cambios que entran en vigencia el 1 de agosto.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'policy_update',
                'target_audience' => ['users', 'agents', 'admins'],
                'summary' => 'Nuevas políticas de privacidad vigentes desde agosto',
                'call_to_action' => [
                    'text' => 'Leer políticas actualizadas',
                    'url' => 'https://pilandina.com.bo/privacidad',
                ],
            ],
            'published_at' => '2025-07-20 09:00:00',
        ]);

        // August - INCIDENT (Resolved): API Outage
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Interrupción del Servicio API',
            'content' => 'El servicio de API experimentó una interrupción debido a un problema con el proveedor de hosting. El servicio ha sido completamente restaurado.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => true,
                'started_at' => '2025-08-10T10:00:00Z',
                'ended_at' => '2025-08-10T12:30:00Z',
                'resolved_at' => '2025-08-10T12:30:00Z',
                'resolution_content' => 'El proveedor de hosting resolvió un problema en su infraestructura. Se implementaron redundancias adicionales para prevenir futuros incidentes.',
                'affected_services' => ['API REST', 'Integraciones de Terceros'],
            ],
            'published_at' => '2025-08-10 10:00:00',
        ]);

        // September - NEWS: Training Program
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Nuevo Programa de Capacitación para Distribuidores',
            'content' => 'Lanzamos nuestro programa de capacitación virtual para distribuidores. El programa incluye módulos sobre manejo de productos, atención al cliente y uso del sistema de pedidos.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['agents'],
                'summary' => 'Programa de capacitación disponible para distribuidores',
                'call_to_action' => [
                    'text' => 'Inscribirse al programa',
                    'url' => 'https://pilandina.com.bo/capacitacion',
                ],
            ],
            'published_at' => '2025-09-01 08:00:00',
        ]);

        // October - ALERT: System Compliance
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Recordatorio: Verificación de Datos Fiscales',
            'content' => 'Se recuerda a todos los distribuidores que deben verificar y actualizar sus datos fiscales en el sistema antes del cierre del año fiscal.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'compliance',
                'message' => 'Actualice sus datos fiscales antes del 31 de octubre',
                'action_required' => true,
                'action_description' => 'Acceda a Mi Perfil > Datos Fiscales y verifique la información',
                'started_at' => '2025-10-01T00:00:00Z',
                'ended_at' => '2025-10-31T23:59:59Z',
            ],
            'published_at' => '2025-10-01 08:00:00',
        ]);

        // November - INCIDENT (Active): Current Issue
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Investigando - Lentitud en el Sistema de Reportes',
            'content' => 'Hemos detectado lentitud en la generación de reportes. Nuestro equipo técnico está investigando la causa. Actualizaremos este anuncio con más información.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => '2025-11-18T14:00:00Z',
                'affected_services' => ['Sistema de Reportes', 'Exportación de Datos'],
            ],
            'published_at' => '2025-11-18 14:00:00',
        ]);

        // November - MAINTENANCE (Upcoming)
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Programado - Actualización de Seguridad',
            'content' => 'Se realizará una actualización de seguridad en todos nuestros sistemas. Durante el mantenimiento, el acceso estará temporalmente interrumpido.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-11-25T02:00:00Z',
                'scheduled_end' => '2025-11-25T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Portal Web', 'API', 'Sistema de Pedidos'],
            ],
            'published_at' => '2025-11-19 08:00:00',
        ]);

        // ===== DRAFT ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Cierre por Fiestas de Fin de Año',
            'content' => 'Informamos que nuestras oficinas estarán cerradas durante las fiestas de fin de año. El sistema de pedidos seguirá operativo.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Horarios especiales durante fiestas de fin de año',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Nueva Funcionalidad: Seguimiento de Pedidos en Tiempo Real',
            'content' => 'Próximamente lanzaremos una nueva funcionalidad que permitirá a los distribuidores hacer seguimiento de sus pedidos en tiempo real.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Seguimiento de pedidos en tiempo real próximamente',
            ],
            'published_at' => null,
        ]);

        // ===== SCHEDULED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento de Servidores - Diciembre',
            'content' => 'Mantenimiento preventivo programado para el mes de diciembre. Se realizarán actualizaciones de hardware y software.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'urgency' => 'LOW',
                'scheduled_start' => '2025-12-15T00:00:00Z',
                'scheduled_end' => '2025-12-15T04:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Todos los servicios'],
                'scheduled_for' => '2025-12-10T08:00:00Z',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Promociones Especiales de Navidad',
            'content' => 'Este diciembre tendremos promociones especiales en toda nuestra línea de productos. ¡No se las pierdan!',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'news_type' => 'general_update',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Promociones navideñas en productos PIL',
                'scheduled_for' => '2025-12-01T08:00:00Z',
            ],
            'published_at' => null,
        ]);

        // ===== ARCHIVED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Completado - Sistema de Facturación',
            'content' => 'El mantenimiento del sistema de facturación se completó exitosamente. Todos los servicios están operativos.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-01-10T02:00:00Z',
                'scheduled_end' => '2025-01-10T05:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Sistema de Facturación'],
                'actual_start' => '2025-01-10T02:00:00Z',
                'actual_end' => '2025-01-10T04:30:00Z',
            ],
            'published_at' => '2025-01-08 10:00:00',
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Error en Cálculo de Descuentos',
            'content' => 'Se detectó y corrigió un error en el cálculo de descuentos por volumen. Los pedidos afectados fueron recalculados.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => true,
                'started_at' => '2025-02-01T09:00:00Z',
                'ended_at' => '2025-02-01T11:30:00Z',
                'resolved_at' => '2025-02-01T11:30:00Z',
                'resolution_content' => 'Se corrigió la fórmula de cálculo de descuentos y se recalcularon todos los pedidos del día.',
                'affected_services' => ['Sistema de Pedidos', 'Facturación'],
            ],
            'published_at' => '2025-02-01 09:00:00',
        ]);

        $this->command->info('PIL Andina announcements created successfully!');
        $this->command->info('- 12 Published announcements');
        $this->command->info('- 2 Draft announcements');
        $this->command->info('- 2 Scheduled announcements');
        $this->command->info('- 2 Archived announcements');
    }
}
