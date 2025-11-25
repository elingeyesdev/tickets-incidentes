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
 * Tigo Bolivia Announcements Seeder
 *
 * Creates sample announcements for Tigo Bolivia S.A. company with:
 * - Various types (MAINTENANCE, INCIDENT, NEWS, ALERT)
 * - Different statuses (PUBLISHED, DRAFT, SCHEDULED, ARCHIVED)
 * - Dates from early 2025 to November 2025
 * - Realistic telecommunications sector announcements
 */
class TigoAnnouncementsSeeder extends Seeder
{
    public function run(): void
    {
        // Find Tigo company
        $company = Company::where('name', 'Tigo Bolivia S.A.')->first();

        if (!$company) {
            $this->command->error('Tigo Bolivia S.A. company not found. Please run RealBolivianCompaniesSeeder first.');
            return;
        }

        // Find company admin
        $admin = User::where('email', 'ricardo.martinez@tigo.com.bo')->first();

        if (!$admin) {
            $this->command->error('Tigo company admin not found.');
            return;
        }

        $this->command->info('Creating announcements for Tigo...');

        // ===== PUBLISHED ANNOUNCEMENTS =====

        // January - NEWS: 4G Expansion
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Expansión de Cobertura 4G - 50 Nuevas Ciudades',
            'content' => 'Tigo Bolivia ha expandido su cobertura 4G LTE a 50 nuevas ciudades y municipios del país. Ahora ofrecemos las velocidades más rápidas de internet móvil en Bolivia con conectividad en más zonas rurales.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'network_expansion',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Expansión de 4G a 50 nuevas ciudades',
                'call_to_action' => [
                    'text' => 'Verifica cobertura en tu zona',
                    'url' => 'https://www.tigo.com.bo/cobertura-4g',
                ],
            ],
            'published_at' => '2025-01-10 08:00:00',
        ]);

        // February - MAINTENANCE: Network Upgrade
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Actualización de Red - Mejoras en Velocidad y Estabilidad',
            'content' => 'Se realizará una actualización mayor de la infraestructura de red en La Paz y Cochabamba. Se espera mejorar las velocidades de conexión en un 40% y reducir la latencia en videollamadas.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-02-18T23:00:00Z',
                'scheduled_end' => '2025-02-19T05:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Red 4G', 'Datos Móviles'],
                'actual_start' => '2025-02-18T23:00:00Z',
                'actual_end' => '2025-02-19T04:30:00Z',
            ],
            'published_at' => '2025-02-14 10:00:00',
        ]);

        // March - INCIDENT (Resolved): Tower Damage
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Daño en Torre de Telecomunicaciones',
            'content' => 'Una torre de telecomunicaciones en el Alto fue dañada por fuertes vientos. Nuestro equipo técnico reparó la estructura e instaló antenas de respaldo. El servicio ha sido completamente restaurado.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => true,
                'started_at' => '2025-03-08T14:30:00Z',
                'ended_at' => '2025-03-09T18:00:00Z',
                'resolved_at' => '2025-03-09T18:00:00Z',
                'resolution_content' => 'Se reparó la estructura dañada de la torre. Se instalaron antenas adicionales para mejorar la cobertura en el área afectada.',
                'affected_services' => ['Cobertura Móvil El Alto', 'Internet Banda Ancha'],
            ],
            'published_at' => '2025-03-08 14:45:00',
        ]);

        // April - NEWS: 5G Pilot Program
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Programa Piloto 5G - La Paz y Cochabamba',
            'content' => 'Tigo Bolivia comienza un programa piloto de tecnología 5G en La Paz y Cochabamba. Usuarios seleccionados podrán experimentar velocidades de hasta 1 Gbps. La tecnología 5G revolucionará la conectividad en Bolivia.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'technology_initiative',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Lanzamiento de programa piloto 5G',
                'call_to_action' => [
                    'text' => 'Registrate para el piloto 5G',
                    'url' => 'https://www.tigo.com.bo/5g-pilot',
                ],
            ],
            'published_at' => '2025-04-12 09:00:00',
        ]);

        // May - ALERT: Data Plan Changes
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Cambios en Planes de Datos - Actualización de Política',
            'content' => 'A partir del 1 de junio, se actualizarán los planes de datos de Tigo. Se aumentará la velocidad base en todos los planes. Los clientes actuales serán notificados individualmente de los cambios que les aplican.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'alert_type' => 'policy_change',
                'message' => 'Nuevos planes de datos con mayor velocidad',
                'action_required' => true,
                'action_description' => 'Revisa tu plan actual en Mi Cuenta > Planes y Servicios',
                'started_at' => '2025-05-01T00:00:00Z',
                'ended_at' => '2025-06-01T23:59:59Z',
            ],
            'published_at' => '2025-05-08 10:30:00',
        ]);

        // June - NEWS: Mobile Payments Launch
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Lanzamiento - Tigo Money Digital',
            'content' => 'Tigo Bolivia lanza Tigo Money Digital, una billetera móvil que permite pagos sin contacto, transferencias instantáneas y pago de servicios. Integrado directamente en tu plan Tigo.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'feature_release',
                'target_audience' => ['users', 'agents'],
                'summary' => 'Nueva billetera digital Tigo Money',
                'call_to_action' => [
                    'text' => 'Descargar Tigo Money Digital',
                    'url' => 'https://www.tigo.com.bo/tigo-money',
                ],
            ],
            'published_at' => '2025-06-16 09:00:00',
        ]);

        // July - MAINTENANCE: Core Network Update
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Actualización de Red Troncal - Junio 2025',
            'content' => 'Se realizará una actualización de la red troncal (backbone) de Tigo Bolivia. Se esperan mejoras significativas en la velocidad de datos a nivel nacional.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-07-09T22:00:00Z',
                'scheduled_end' => '2025-07-10T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Internet Banda Ancha', 'Datos Móviles'],
                'actual_start' => '2025-07-09T22:00:00Z',
                'actual_end' => '2025-07-10T05:15:00Z',
            ],
            'published_at' => '2025-07-07 10:00:00',
        ]);

        // August - INCIDENT (Resolved): Routing Issue
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Problema de Enrutamiento de Datos',
            'content' => 'Se presentó un problema en el enrutamiento de datos internacionales causando lentitud en la navegación. El equipo de NOC identificó y corrigió la configuración. El servicio funciona normalmente.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'is_resolved' => true,
                'started_at' => '2025-08-05T09:00:00Z',
                'ended_at' => '2025-08-05T11:30:00Z',
                'resolved_at' => '2025-08-05T11:30:00Z',
                'resolution_content' => 'Se identificó una configuración incorrecta en los routers de borde. Se actualizó la configuración de rutas BGP y se normalizó el tráfico internacional.',
                'affected_services' => ['Navegación Internacional', 'Datos Internacionales'],
            ],
            'published_at' => '2025-08-05 11:45:00',
        ]);

        // September - NEWS: Customer Loyalty Program
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Programa de Lealtad Mejorado - Tigo Rewards 2.0',
            'content' => 'Tigo lanza una versión mejorada de su programa de lealtad con más beneficios y canjes. Gana puntos en cada recarga y canjéalos por minutos, datos o descuentos en servicios premium.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'news_type' => 'customer_program',
                'target_audience' => ['users'],
                'summary' => 'Nuevo programa de lealtad Tigo Rewards 2.0',
                'call_to_action' => [
                    'text' => 'Conocer más sobre Tigo Rewards',
                    'url' => 'https://www.tigo.com.bo/rewards',
                ],
            ],
            'published_at' => '2025-09-11 08:30:00',
        ]);

        // October - ALERT: SIM Card Replacement
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Programa de Reemplazo de Tarjetas SIM - Seguridad',
            'content' => 'Por razones de seguridad, Tigo Bolivia está reemplazando todas las tarjetas SIM 2G antiguas por SIM 4G modernas. Solicita tu SIM nueva en cualquier tienda Tigo antes del 31 de octubre.',
            'type' => AnnouncementType::ALERT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'HIGH',
                'alert_type' => 'security',
                'message' => 'Reemplaza tu SIM antes del 31 de octubre',
                'action_required' => true,
                'action_description' => 'Acude a la tienda Tigo más cercana con tu documento de identidad',
                'started_at' => '2025-10-01T00:00:00Z',
                'ended_at' => '2025-10-31T23:59:59Z',
            ],
            'published_at' => '2025-10-02 09:00:00',
        ]);

        // November - INCIDENT (Active): Network Congestion
        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Congestión de Red - Investigación en Progreso',
            'content' => 'Se ha detectado congestión en la red de datos en Santa Cruz durante horas pico (18:00-22:00). El equipo técnico está optimizando la capacidad. Se espera resolución en 48 horas.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::PUBLISHED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'is_resolved' => false,
                'started_at' => '2025-11-21T18:00:00Z',
                'affected_services' => ['Datos Móviles Santa Cruz', 'Velocidad de Internet'],
            ],
            'published_at' => '2025-11-21 18:30:00',
        ]);

        // ===== DRAFT ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Tigo Hogar Inteligente - IoT en Desarrollo',
            'content' => 'Estamos desarrollando Tigo Hogar Inteligente, una plataforma IoT que conecta tu casa. Control remoto de luces, temperatura, seguridad y más desde tu móvil.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'future_product',
                'target_audience' => ['users'],
                'summary' => 'Plataforma IoT Tigo Hogar Inteligente próximamente',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Servicio de Cloud Gaming - En Pruebas',
            'content' => 'Tigo está probando un servicio de cloud gaming que permite jugar videojuegos AAA sin necesidad de consola. Solo necesitas una conexión 4G/5G estable.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::DRAFT,
            'metadata' => [
                'news_type' => 'future_service',
                'target_audience' => ['users'],
                'summary' => 'Servicio de cloud gaming en desarrollo',
            ],
            'published_at' => null,
        ]);

        // ===== SCHEDULED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Campaña de Fin de Año - Diciembre 2025',
            'content' => 'Tigo lanza su gran campaña navideña con planes especiales, bonificaciones de datos y entretenimiento ilimitado. Ofertas disponibles desde el 1 de diciembre.',
            'type' => AnnouncementType::NEWS,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'news_type' => 'seasonal_promotion',
                'target_audience' => ['users'],
                'summary' => 'Campaña navideña con ofertas especiales',
                'scheduled_for' => '2025-12-01T08:00:00Z',
            ],
            'published_at' => null,
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento de Infraestructura - Diciembre',
            'content' => 'Se realizará mantenimiento de la infraestructura de telecomunicaciones en zonas periurbanas. Se esperan interrupciones de entre 2 a 4 horas en horarios nocturnos.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::SCHEDULED,
            'metadata' => [
                'urgency' => 'LOW',
                'scheduled_start' => '2025-12-05T22:00:00Z',
                'scheduled_end' => '2025-12-15T06:00:00Z',
                'is_emergency' => false,
                'affected_services' => ['Cobertura Zonas Periurbanas'],
                'scheduled_for' => '2025-11-28T10:00:00Z',
            ],
            'published_at' => null,
        ]);

        // ===== ARCHIVED ANNOUNCEMENTS =====

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Mantenimiento Completado - Red 4G La Paz',
            'content' => 'El mantenimiento de la red 4G en La Paz se completó exitosamente. Se observan mejoras en velocidad y estabilidad en toda la ciudad.',
            'type' => AnnouncementType::MAINTENANCE,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'MEDIUM',
                'scheduled_start' => '2025-01-20T00:00:00Z',
                'scheduled_end' => '2025-01-22T23:59:59Z',
                'is_emergency' => false,
                'affected_services' => ['Red 4G La Paz'],
                'actual_start' => '2025-01-20T00:00:00Z',
                'actual_end' => '2025-01-22T18:00:00Z',
            ],
            'published_at' => '2025-01-17 10:00:00',
        ]);

        Announcement::create([
            'id' => Str::uuid(),
            'company_id' => $company->id,
            'author_id' => $admin->id,
            'title' => 'Incidente Resuelto - Caída de Servicio de Datos',
            'content' => 'Se detectó una caída del servicio de datos en Santa Cruz por falla de equipos. Se realizó el cambio a equipamiento de respaldo. El servicio se normalizó.',
            'type' => AnnouncementType::INCIDENT,
            'status' => PublicationStatus::ARCHIVED,
            'metadata' => [
                'urgency' => 'CRITICAL',
                'is_resolved' => true,
                'started_at' => '2025-05-11T13:00:00Z',
                'ended_at' => '2025-05-11T14:30:00Z',
                'resolved_at' => '2025-05-11T14:30:00Z',
                'resolution_content' => 'Se realizó cambio a equipos redundantes. Se identificó el equipo defectuoso y será enviado a reparación. Se instaló equipo de reemplazo.',
                'affected_services' => ['Datos Móviles Santa Cruz'],
            ],
            'published_at' => '2025-05-11 13:15:00',
        ]);

        $this->command->info('Tigo announcements created successfully!');
        $this->command->info('- 11 Published announcements');
        $this->command->info('- 2 Draft announcements');
        $this->command->info('- 2 Scheduled announcements');
        $this->command->info('- 2 Archived announcements');
    }
}
