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
 * Cervecería Boliviana Nacional Announcements Seeder
 *
 * Creates sample announcements for Cervecería Boliviana Nacional S.A. company with:
 * - Various types (MAINTENANCE, INCIDENT, NEWS, ALERT)
 * - Different statuses (PUBLISHED, DRAFT, SCHEDULED, ARCHIVED)
 * - Dates from early 2025 to November 2025
 * - Realistic beverage manufacturing sector announcements
 */
class CerveceriaBolividanaAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Find CBN company
        $company = Company::where('name', 'Cervecería Boliviana Nacional S.A.')->first();

        if (!$company) {
            $this->command->error('Cervecería Boliviana Nacional S.A. company not found. Please run RealBolivianCompaniesSeeder first.');
            return;
        }

        // Find company admin
        $admin = User::where('email', 'alejandro.reyes@cbn.bo')->first();

        if (!$admin) {
            $this->command->error('CBN company admin not found.');
            return;
        }

        $this->command->info('Creating announcements for Cervecería Boliviana Nacional...');

        // ===== PUBLISHED ANNOUNCEMENTS =====

        // January - NEWS: New Product Line
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Lanzamiento: Nueva Línea de Cervezas Artesanales Premium',
            'content' => 'CBN presenta su nueva línea de cervezas artesanales premium con sabores únicos: IPA Andina, Porter Cochabambina y Lager Paceña. Producidas con ingredientes naturales de Bolivia.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'product_launch',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Nueva línea de cervezas artesanales premium',
                'call_to_action' => [
                    'text' => 'Encuentra nuestros productos',
                    'url' => 'https://www.cbn.bo/productos-premium',
                ],
            ],
            'published_at' => '2025-01-15 10:00:00',
        ]);

        // February - MAINTENANCE: Production Line Upgrade
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Mayor - Modernización de Línea de Producción',
            'content' => 'Se realizará un mantenimiento mayor en la línea de producción principal. Se instalarán máquinas modernas que aumentarán la eficiencia un 35%. Se espera reducción temporal en producción.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-02-17T22:00:00Z',
                'scheduled_end' => '2025-03-03T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Línea Principal de Cerveza', 'Distribución'],
                'actual_start' => '2025-02-17T22:00:00Z',
                'actual_end' => '2025-03-02T18:00:00Z',
            ],
            'published_at' => '2025-02-12 09:30:00',
        ]);

        // March - NEWS: Sustainability Initiative
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Iniciativa de Sostenibilidad - Botellas 100% Reciclables',
            'content' => 'CBN se compromete con la sostenibilidad ambiental. Todas nuestras botellas serán 100% reciclables. Lanzamos programa de reciclaje en puntos de venta para recuperar botellas usadas.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'sustainability_initiative',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Compromiso con botellas 100% reciclables',
                'call_to_action' => [
                    'text' => 'Participa en nuestro programa de reciclaje',
                    'url' => 'https://www.cbn.bo/reciclaje',
                ],
            ],
            'published_at' => '2025-03-10 08:00:00',
        ]);

        // April - ALERT: Quality Control Update
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Alerta de Calidad - Nuevo Protocolo de Control',
            'content' => 'Se ha implementado un nuevo protocolo de control de calidad más riguroso. Todos los lotes de producción ahora pasan por 7 fases de inspección. Esto garantiza la máxima calidad en nuestros productos.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'alert_type' => 'quality_assurance',
                'message' => 'Nuevo protocolo de control de calidad implementado',
                'action_required' => false,
                'started_at' => '2025-04-01T00:00:00Z',
            ],
            'published_at' => '2025-04-02 09:00:00',
        ]);

        // May - NEWS: Distribution Expansion
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Expansión de Distribución - 500 Nuevos Puntos de Venta',
            'content' => 'CBN ha expandido su red de distribución a 500 nuevos puntos de venta en zonas rurales. Ahora disponibles en más tiendas pequeñas y minimercados de todo el país.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'business_expansion',
                'target_audience' => ['agents', 'users'],
                'summary' => 'Expansión a 500 nuevos puntos de venta',
                'call_to_action' => [
                    'text' => 'Encuentra CBN cerca de ti',
                    'url' => 'https://www.cbn.bo/localizador-tiendas',
                ],
            ],
            'published_at' => '2025-05-12 10:30:00',
        ]);

        // June - INCIDENT (Resolved): Refrigeration System
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Falla en Sistema de Refrigeración',
            'content' => 'Se detectó una falla en el sistema de refrigeración de nuestro almacén principal. Se reparó inmediatamente el equipo. Ningún producto fue afectado gracias a nuestros sistemas redundantes.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => true,
                'started_at' => '2025-06-08T14:30:00Z',
                'ended_at' => '2025-06-08T16:00:00Z',
                'resolved_at' => '2025-06-08T16:00:00Z',
                'resolution_content' => 'Se reemplazó el compresor defectuoso del sistema de refrigeración. Se realizó mantenimiento preventivo de todo el sistema. Sistema funcionando óptimamente.',
                'affected_services' => ['Almacenamiento Refrigerado', 'Cadena de Frío'],
            ],
            'published_at' => '2025-06-08 14:45:00',
        ]);

        // July - NEWS: Marketing Campaign
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Campaña "Sabor Boliviano" - Celebra con CBN',
            'content' => 'CBN lanza su campaña "Sabor Boliviano" promoviendo la cultura y tradiciones del país. Colecciones limitadas especiales y envases edición limitada con diseños inspirados en la historia boliviana.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'marketing_campaign',
                'target_audience' => ['users'],
                'summary' => 'Campaña "Sabor Boliviano" con ediciones limitadas',
                'call_to_action' => [
                    'text' => 'Colecciona ediciones limitadas',
                    'url' => 'https://www.cbn.bo/sabor-boliviano',
                ],
            ],
            'published_at' => '2025-07-05 09:00:00',
        ]);

        // August - MAINTENANCE: Quality Lab Upgrade
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Actualización del Laboratorio de Calidad',
            'content' => 'Se han instalado nuevos equipos de análisis en nuestro laboratorio de control de calidad. Estos equipos permiten detección de contaminantes con mayor precisión y rapidez.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'LOW',
                'scheduled_start' => '2025-08-10T18:00:00Z',
                'scheduled_end' => '2025-08-11T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Laboratorio de Calidad'],
                'actual_start' => '2025-08-10T18:00:00Z',
                'actual_end' => '2025-08-11T05:00:00Z',
            ],
            'published_at' => '2025-08-08 10:00:00',
        ]);

        // September - NEWS: Sponsorship
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Sponsorship - CBN Patrocina Copa Americana',
            'content' => 'CBN se enorgullece en anunciar que será patrocinador oficial de la Copa Americana de Fútbol. Apoyamos el talento deportivo y la pasión por el fútbol en toda América del Sur.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'sponsorship',
                'target_audience' => ['users', 'agents'],
                'summary' => 'CBN patrocina Copa Americana',
            ],
            'published_at' => '2025-09-06 10:00:00',
        ]);

        // October - ALERT: Distributor Training
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Capacitación Obligatoria - Nuevas Normas de Distribución',
            'content' => 'Se han implementado nuevas normas de distribución. Todos los distribuidores deben completar capacitación obligatoria antes del 31 de octubre. Certificación requerida para continuar operando.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'training_required',
                'message' => 'Capacitación obligatoria antes del 31 de octubre',
                'action_required' => true,
                'action_description' => 'Inscribete en el portal de distribuidores y completa los módulos de capacitación',
                'started_at' => '2025-10-01T00:00:00Z',
                'ended_at' => '2025-10-31T23:59:59Z',
            ],
            'published_at' => '2025-10-05 09:00:00',
        ]);

        // November - INCIDENT (Active): Production Slowdown
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Investigando - Fluctuaciones en Línea de Embotellado',
            'content' => 'Se ha detectado fluctuaciones en la velocidad de la línea de embotellado. El equipo técnico está investigando la causa. La producción continúa pero a menor velocidad.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => '2025-11-19T08:00:00Z',
                'affected_services' => ['Línea de Embotellado', 'Producción'],
            ],
            'published_at' => '2025-11-19 08:30:00',
        ]);

        // ===== DRAFT ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Nueva Bebida: Agua Carbonatada Natural - En Desarrollo',
            'content' => 'CBN está desarrollando una nueva línea de agua carbonatada natural con minerales de las montañas bolivianas. Lanzamiento previsto para 2026.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'future_product',
                'target_audience' => ['users'],
                'summary' => 'Nueva línea de agua carbonatada en desarrollo',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Experiencia CBN - Centro de Visitas Interactivo',
            'content' => 'Se está construyendo un centro de visitas interactivo donde podrás ver el proceso de producción de nuestras bebidas. Incluye degustación y tienda de productos exclusivos.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'infrastructure_project',
                'target_audience' => ['users'],
                'summary' => 'Centro de visitas CBN en construcción',
            ],
            'published_at' => null,
        ]);

        // ===== SCHEDULED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Campaña Navideña - Promociones Especiales Diciembre',
            'content' => 'CBN lanza su campaña navideña con promociones especiales: packs familiares a precio especial, botellas regalo y sorteos. Válido desde el 1 de diciembre.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'news_type' => 'seasonal_promotion',
                'target_audience' => ['users'],
                'summary' => 'Promociones navideñas especiales',
                'scheduled_for' => '2025-12-01T08:00:00Z',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento de Planta - Diciembre 2025',
            'content' => 'Se realizará mantenimiento anual de toda la planta de producción. Se actualizarán sistemas de control y se limpiarán equipos. Cierre parcial de producción durante este período.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-12-15T18:00:00Z',
                'scheduled_end' => '2025-12-22T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Toda la Planta de Producción'],
                'scheduled_for' => '2025-11-27T10:00:00Z',
            ],
            'published_at' => null,
        ]);

        // ===== ARCHIVED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Completado - Sistema de Filtración',
            'content' => 'El mantenimiento del sistema de filtración de agua se completó exitosamente. Se instalaron filtros nuevos de última generación para mejorar la pureza del agua de producción.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-01-13T22:00:00Z',
                'scheduled_end' => '2025-01-14T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Sistema de Filtración'],
                'actual_start' => '2025-01-13T22:00:00Z',
                'actual_end' => '2025-01-14T05:30:00Z',
            ],
            'published_at' => '2025-01-10 10:00:00',
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Contaminación Detectada en Lote',
            'content' => 'Se detectó contaminación en un lote de producción durante el control de calidad. El lote fue descartado inmediatamente. Se investigó la causa y se implementaron medidas correctivas.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => true,
                'started_at' => '2025-04-25T09:30:00Z',
                'ended_at' => '2025-04-25T12:00:00Z',
                'resolved_at' => '2025-04-25T12:00:00Z',
                'resolution_content' => 'Se identificó una microfisura en un sensor de temperatura que causaba lecturas incorrectas. Se reemplazó el sensor y se desinfectó el equipo. Se descartó el lote contaminado.',
                'affected_services' => ['Línea de Producción 2'],
            ],
            'published_at' => '2025-04-25 09:45:00',
        ]);

        $this->command->info('Cervecería Boliviana Nacional announcements created successfully!');
        $this->command->info('- 11 Published announcements');
        $this->command->info('- 2 Draft announcements');
        $this->command->info('- 2 Scheduled announcements');
        $this->command->info('- 2 Archived announcements');
    }
}
