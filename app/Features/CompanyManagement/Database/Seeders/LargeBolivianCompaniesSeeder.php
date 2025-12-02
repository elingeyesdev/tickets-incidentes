<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Area;
use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Large Bolivian Companies Seeder
 *
 * Crea 6 empresas bolivianas GRANDES (estatales, multinacionales, líderes absolutos) con:
 * - company_code FIJO (formato CMP-2025-0000X) → determinístico, no duplica logos
 * - 1 Company Admin por empresa
 * - 2 Agentes por empresa
 * - 7-8 Áreas/Departamentos por empresa (estructura organizacional completa)
 * - areas_enabled = true (funcionalidad activada)
 * - Logos copiados automáticamente de resources → storage (idempotente)
 * - Todos los usuarios con contraseña: mklmklmkl
 * - industry_id asignado correctamente
 *
 * Empresas GRANDES (estatales, multinacionales, líderes absolutos):
 * 1. PIL Andina (CMP-2025-00001) - Productos Lácteos (7 áreas) - Líder nacional lácteos
 * 2. YPFB (CMP-2025-00002) - Petróleo y Gas (8 áreas) - Empresa estatal estratégica
 * 3. Entel (CMP-2025-00003) - Telecomunicaciones (7 áreas) - Empresa estatal líder telecom
 * 4. Tigo (CMP-2025-00004) - Telecomunicaciones (7 áreas) - Multinacional (Millicom)
 * 5. CBN (CMP-2025-00005) - Bebidas (7 áreas) - Multinacional (AB InBev)
 * 6. Banco Mercantil Santa Cruz (CMP-2025-00006) - Servicios Financieros (7 áreas) - Banco más grande
 *
 * Estructura de logos (determinística, sin timestamps):
 * storage/app/public/company-logos/
 * ├── CMP-2025-00001/pil-andina-logo.png
 * ├── CMP-2025-00002/ypfb-logo.png
 * ├── CMP-2025-00003/entel-logo.png
 * ├── CMP-2025-00004/tigo-logo.png
 * ├── CMP-2025-00005/cbn-logo.png
 * └── CMP-2025-00006/mercantil-santa-cruz-logo.png
 *
 * Beneficios:
 * - Idempotente: ejecutar múltiples veces no duplica logos
 * - Sin manual: logos se copian automáticamente desde resources
 * - Determinístico: mismo company_code = misma carpeta = misma URL
 *
 */
class LargeBolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private const COMPANIES = [
        [
            'company_code' => 'CMP-2025-00001',
            'name' => 'PIL Andina S.A.',
            'legal_name' => 'PIL Andina S.A. - Productora Integral Láctea',
            'description' => 'Empresa líder en producción y comercialización de productos lácteos de alta calidad en Bolivia',
            'support_email' => 'soporte@pilandina.com.bo',
            'phone' => '+59144260164',
            'city' => 'Cochabamba',
            'address' => 'Colcapirhua Avenida Blanco Galindo Km. 10.5',
            'state' => 'Cochabamba',
            'postal_code' => '00000',
            'tax_id' => '151099010',
            'legal_rep' => 'Javier Rodríguez García',
            'website' => 'https://pilandina.com.bo',
            'industry_code' => 'manufacturing',
            'primary_color' => '#E31E24',
            'secondary_color' => '#FFFFFF',
            'logo_filename' => 'pil-andina-logo.png',
            'company_admin' => [
                'first_name' => 'Javier',
                'last_name' => 'Rodríguez',
                'email' => 'javier.rodriguez@pilandina.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'María',
                    'last_name' => 'Condori',
                    'email' => 'maria.condori@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Roberto',
                    'last_name' => 'Flores',
                    'email' => 'roberto.flores@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Ana',
                    'last_name' => 'Mamani',
                    'email' => 'ana.mamani@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Gutiérrez',
                    'email' => 'carlos.gutierrez@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Lucía',
                    'last_name' => 'Quispe',
                    'email' => 'lucia.quispe@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Jorge',
                    'last_name' => 'Vargas',
                    'email' => 'jorge.vargas@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Patricia',
                    'last_name' => 'Rojas',
                    'email' => 'patricia.rojas@pilandina.com.bo',
                ],
                [
                    'first_name' => 'Fernando',
                    'last_name' => 'Mendoza',
                    'email' => 'fernando.mendoza@pilandina.com.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Producción y Procesamiento',
                    'description' => 'Manufactura, pasteurización, empaque de lácteos',
                ],
                [
                    'name' => 'Control de Calidad',
                    'description' => 'Análisis de productos, pruebas de laboratorio, cumplimiento de estándares',
                ],
                [
                    'name' => 'Logística y Distribución',
                    'description' => 'Gestión de inventario, transporte, entregas a puntos de venta',
                ],
                [
                    'name' => 'Ventas y Comercial',
                    'description' => 'Gestión de clientes, canales de distribución, negociaciones comerciales',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación, desarrollo del personal',
                ],
                [
                    'name' => 'Administración y Finanzas',
                    'description' => 'Contabilidad, presupuestos, tesorería, asuntos administrativos',
                ],
                [
                    'name' => 'Mantenimiento e Infraestructura',
                    'description' => 'Mantenimiento de equipos, infraestructura, seguridad industrial',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00002',
            'name' => 'YPFB Corporación',
            'legal_name' => 'Yacimientos Petrolíferos Fiscales Bolivianos S.A.',
            'description' => 'Empresa estatal boliviana encargada de la exploración, explotación, refinación, transporte y comercialización de hidrocarburos',
            'support_email' => 'contacto@ypfb.gob.bo',
            'phone' => '+59122106565',
            'city' => 'La Paz',
            'address' => 'Calle Bueno Nº 185, Centro',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151070001',
            'legal_rep' => 'Luis Alberto Sánchez Fernández',
            'website' => 'https://www.ypfb.gob.bo',
            'industry_code' => 'energy',
            'primary_color' => '#00529B',
            'secondary_color' => '#FDB913',
            'logo_filename' => 'ypfb-logo.png',
            'company_admin' => [
                'first_name' => 'Luis',
                'last_name' => 'Sánchez',
                'email' => 'luis.sanchez@ypfb.gob.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Patricia',
                    'last_name' => 'Alanoca',
                    'email' => 'patricia.alanoca@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Miguel',
                    'last_name' => 'Pacheco',
                    'email' => 'miguel.pacheco@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Carla',
                    'last_name' => 'Mendoza',
                    'email' => 'carla.mendoza@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Ricardo',
                    'last_name' => 'Torres',
                    'email' => 'ricardo.torres@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Daniela',
                    'last_name' => 'Villarroel',
                    'email' => 'daniela.villarroel@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Andrés',
                    'last_name' => 'Guzmán',
                    'email' => 'andres.guzman@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Mónica',
                    'last_name' => 'Ramos',
                    'email' => 'monica.ramos@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Héctor',
                    'last_name' => 'Morales',
                    'email' => 'hector.morales@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Silvia',
                    'last_name' => 'Camacho',
                    'email' => 'silvia.camacho@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Rodrigo',
                    'last_name' => 'Bustamante',
                    'email' => 'rodrigo.bustamante@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Gabriela',
                    'last_name' => 'Salazar',
                    'email' => 'gabriela.salazar@ypfb.gob.bo',
                ],
                [
                    'first_name' => 'Javier',
                    'last_name' => 'Ortiz',
                    'email' => 'javier.ortiz@ypfb.gob.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Exploración y Producción',
                    'description' => 'Operaciones de pozo, producción de hidrocarburos, levantamiento artificial',
                ],
                [
                    'name' => 'Refinación y Transformación',
                    'description' => 'Procesos de refinería, destilación, conversión de crudos',
                ],
                [
                    'name' => 'Transporte y Ductos',
                    'description' => 'Operación de oleoductos, gaseoductos, logística de productos',
                ],
                [
                    'name' => 'Comercialización y Ventas',
                    'description' => 'Venta de combustibles, gas natural, productos refinados',
                ],
                [
                    'name' => 'Seguridad, Salud y Ambiente',
                    'description' => 'Seguridad industrial, gestión ambiental, cumplimiento de normativas',
                ],
                [
                    'name' => 'Ingeniería y Proyectos',
                    'description' => 'Diseño, construcción, mantenimiento de instalaciones',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación, relaciones laborales',
                ],
                [
                    'name' => 'Administración y Finanzas',
                    'description' => 'Contabilidad, presupuestos, asuntos legales, administración corporativa',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00003',
            'name' => 'Entel S.A.',
            'legal_name' => 'Empresa Nacional de Telecomunicaciones S.A.',
            'description' => 'Empresa estatal líder de telecomunicaciones en Bolivia, ofreciendo servicios de telefonía móvil, internet, datos y televisión con cobertura nacional',
            'support_email' => 'atencionodeco@entel.bo',
            'phone' => '+59122141010',
            'city' => 'La Paz',
            'address' => 'C. Federico Zuazo N° 1771, Zona Centro',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '1020703023',
            'legal_rep' => 'Juan Carlos Rojas Beltrán',
            'website' => 'https://www.entel.bo',
            'industry_code' => 'professional_services',
            'primary_color' => '#003DA5',
            'secondary_color' => '#00BCF2',
            'logo_filename' => 'entel-logo.png',
            'company_admin' => [
                'first_name' => 'Juan',
                'last_name' => 'Rojas',
                'email' => 'juan.rojas@entel.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Carmen',
                    'last_name' => 'Quispe',
                    'email' => 'carmen.quispe@entel.bo',
                ],
                [
                    'first_name' => 'Diego',
                    'last_name' => 'Fernández',
                    'email' => 'diego.fernandez@entel.bo',
                ],
                [
                    'first_name' => 'Lorena',
                    'last_name' => 'Castro',
                    'email' => 'lorena.castro@entel.bo',
                ],
                [
                    'first_name' => 'Pablo',
                    'last_name' => 'Martínez',
                    'email' => 'pablo.martinez@entel.bo',
                ],
                [
                    'first_name' => 'Verónica',
                    'last_name' => 'Sánchez',
                    'email' => 'veronica.sanchez@entel.bo',
                ],
                [
                    'first_name' => 'Sergio',
                    'last_name' => 'Álvarez',
                    'email' => 'sergio.alvarez@entel.bo',
                ],
                [
                    'first_name' => 'Andrea',
                    'last_name' => 'Rodríguez',
                    'email' => 'andrea.rodriguez@entel.bo',
                ],
                [
                    'first_name' => 'Marcelo',
                    'last_name' => 'Pérez',
                    'email' => 'marcelo.perez@entel.bo',
                ],
                [
                    'first_name' => 'Isabel',
                    'last_name' => 'Gutiérrez',
                    'email' => 'isabel.gutierrez@entel.bo',
                ],
                [
                    'first_name' => 'Gonzalo',
                    'last_name' => 'Vargas',
                    'email' => 'gonzalo.vargas@entel.bo',
                ],
                [
                    'first_name' => 'Natalia',
                    'last_name' => 'Méndez',
                    'email' => 'natalia.mendez@entel.bo',
                ],
                [
                    'first_name' => 'Raúl',
                    'last_name' => 'Jiménez',
                    'email' => 'raul.jimenez@entel.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Red y Operaciones',
                    'description' => 'Infraestructura de red, operación de centrales, mantenimiento de torres y antenas',
                ],
                [
                    'name' => 'Atención al Cliente',
                    'description' => 'Call center, resolución de problemas, servicio al cliente 800101001',
                ],
                [
                    'name' => 'Comercial y Ventas',
                    'description' => 'Adquisición de clientes, planes corporativos, retención de usuarios',
                ],
                [
                    'name' => 'Tecnología e Innovación',
                    'description' => 'Desarrollo de plataformas digitales, 4G/5G, sistemas de información',
                ],
                [
                    'name' => 'Finanzas y Facturación',
                    'description' => 'Facturación de servicios, cobranzas, contabilidad, análisis financiero',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, reclutamiento, capacitación, desarrollo organizacional',
                ],
                [
                    'name' => 'Administración y Cumplimiento',
                    'description' => 'Asuntos legales, compliance regulatorio, gestión administrativa',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00004',
            'name' => 'Tigo Bolivia S.A.',
            'legal_name' => 'Tigo Bolivia S.A. - Telecomunicaciones',
            'description' => 'Operadora de telecomunicaciones móviles en Bolivia, ofreciendo servicios de telefonía, internet y datos',
            'support_email' => 'soporte@tigo.com.bo',
            'phone' => '+5913800175000',
            'city' => 'La Paz',
            'address' => 'Av. Ballivián, Edificio Green Tower, Calacoto',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151158963',
            'legal_rep' => 'Ricardo Martínez Huerta',
            'website' => 'https://www.tigo.com.bo',
            'industry_code' => 'professional_services',
            'primary_color' => '#0033A0',
            'secondary_color' => '#00BCF2',
            'logo_filename' => 'tigo-logo.png',
            'company_admin' => [
                'first_name' => 'Ricardo',
                'last_name' => 'Martínez',
                'email' => 'ricardo.martinez@tigo.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Andrea',
                    'last_name' => 'Vargas',
                    'email' => 'andrea.vargas@tigo.com.bo',
                ],
                [
                    'first_name' => 'David',
                    'last_name' => 'Suárez',
                    'email' => 'david.suarez@tigo.com.bo',
                ],
                [
                    'first_name' => 'Claudia',
                    'last_name' => 'Romero',
                    'email' => 'claudia.romero@tigo.com.bo',
                ],
                [
                    'first_name' => 'Fabián',
                    'last_name' => 'Herrera',
                    'email' => 'fabian.herrera@tigo.com.bo',
                ],
                [
                    'first_name' => 'Paola',
                    'last_name' => 'Vega',
                    'email' => 'paola.vega@tigo.com.bo',
                ],
                [
                    'first_name' => 'Gustavo',
                    'last_name' => 'Ramírez',
                    'email' => 'gustavo.ramirez@tigo.com.bo',
                ],
                [
                    'first_name' => 'Beatriz',
                    'last_name' => 'Silva',
                    'email' => 'beatriz.silva@tigo.com.bo',
                ],
                [
                    'first_name' => 'Oscar',
                    'last_name' => 'Luna',
                    'email' => 'oscar.luna@tigo.com.bo',
                ],
                [
                    'first_name' => 'Fernanda',
                    'last_name' => 'Delgado',
                    'email' => 'fernanda.delgado@tigo.com.bo',
                ],
                [
                    'first_name' => 'Martín',
                    'last_name' => 'Cortez',
                    'email' => 'martin.cortez@tigo.com.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Red y Operaciones',
                    'description' => 'Infraestructura de redes, operación de centrales, mantenimiento de torres',
                ],
                [
                    'name' => 'Atención al Cliente',
                    'description' => 'Servicio al cliente, resolución de problemas, soporte técnico 24/7',
                ],
                [
                    'name' => 'Ventas y Suscriptores',
                    'description' => 'Adquisición de clientes, planes y promociones, retención de usuarios',
                ],
                [
                    'name' => 'Tecnología e Innovación',
                    'description' => 'Desarrollo de sistemas, aplicaciones móviles, modernización de redes',
                ],
                [
                    'name' => 'Finanzas y Facturación',
                    'description' => 'Facturación de servicios, cobranzas, contabilidad, análisis financiero',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, reclutamiento, capacitación, desarrollo del talento',
                ],
                [
                    'name' => 'Administración',
                    'description' => 'Gestión administrativa, asuntos legales, compras, servicios generales',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00005',
            'name' => 'Cervecería Boliviana Nacional S.A.',
            'legal_name' => 'Cervecería Boliviana Nacional S.A. - Bebidas',
            'description' => 'Principal productora de cerveza en Bolivia con marcas icónicas y presencia nacional en el mercado de bebidas',
            'support_email' => 'soporte@cbn.bo',
            'phone' => '+59122455455',
            'city' => 'La Paz',
            'address' => 'Av. Montes #400, Zona Sopocachi',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '151095874',
            'legal_rep' => 'Alejandro Reyes Montoya',
            'website' => 'https://www.cbn.bo',
            'industry_code' => 'manufacturing',
            'primary_color' => '#C8102E',
            'secondary_color' => '#FFD700',
            'logo_filename' => 'cbn-logo.png',
            'company_admin' => [
                'first_name' => 'Alejandro',
                'last_name' => 'Reyes',
                'email' => 'alejandro.reyes@cbn.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Sofía',
                    'last_name' => 'Castellanos',
                    'email' => 'sofia.castellanos@cbn.bo',
                ],
                [
                    'first_name' => 'Juan',
                    'last_name' => 'Espinoza',
                    'email' => 'juan.espinoza@cbn.bo',
                ],
                [
                    'first_name' => 'Laura',
                    'last_name' => 'Molina',
                    'email' => 'laura.molina@cbn.bo',
                ],
                [
                    'first_name' => 'Pedro',
                    'last_name' => 'Aguilar',
                    'email' => 'pedro.aguilar@cbn.bo',
                ],
                [
                    'first_name' => 'Valeria',
                    'last_name' => 'Navarro',
                    'email' => 'valeria.navarro@cbn.bo',
                ],
                [
                    'first_name' => 'Sebastián',
                    'last_name' => 'Ríos',
                    'email' => 'sebastian.rios@cbn.bo',
                ],
                [
                    'first_name' => 'Carolina',
                    'last_name' => 'Campos',
                    'email' => 'carolina.campos@cbn.bo',
                ],
                [
                    'first_name' => 'Ignacio',
                    'last_name' => 'Paredes',
                    'email' => 'ignacio.paredes@cbn.bo',
                ],
                [
                    'first_name' => 'Daniela',
                    'last_name' => 'Núñez',
                    'email' => 'daniela.nunez@cbn.bo',
                ],
                [
                    'first_name' => 'Esteban',
                    'last_name' => 'Ibáñez',
                    'email' => 'esteban.ibanez@cbn.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Producción y Elaboración',
                    'description' => 'Fabricación de cerveza, control de procesos, fermentación, envasado',
                ],
                [
                    'name' => 'Control de Calidad',
                    'description' => 'Análisis sensorial, pruebas microbiológicas, estándares de producto',
                ],
                [
                    'name' => 'Ventas y Distribución',
                    'description' => 'Gestión de canales, relación con distribuidores, puntos de venta',
                ],
                [
                    'name' => 'Marketing y Publicidad',
                    'description' => 'Campañas publicitarias, promociones, gestión de marca y eventos',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Nómina, contratación, capacitación, relaciones laborales',
                ],
                [
                    'name' => 'Administración y Finanzas',
                    'description' => 'Contabilidad, presupuestos, asuntos legales, administración general',
                ],
                [
                    'name' => 'Mantenimiento e Infraestructura',
                    'description' => 'Mantenimiento de planta, infraestructura, seguridad industrial',
                ],
            ],
        ],
        [
            'company_code' => 'CMP-2025-00006',
            'name' => 'Banco Mercantil Santa Cruz S.A.',
            'legal_name' => 'Banco Mercantil Santa Cruz S.A.',
            'description' => 'Banco más grande de Bolivia por activos, ofreciendo servicios bancarios completos con presencia nacional y enfoque en banca corporativa y personal',
            'support_email' => 'CallCenter@bmsc.com.bo',
            'phone' => '+59122409040',
            'city' => 'La Paz',
            'address' => 'Calle Ayacucho esquina Mercado #295, Zona Central',
            'state' => 'La Paz',
            'postal_code' => '00000',
            'tax_id' => '1020557029',
            'legal_rep' => 'Pablo Antelo Gutiérrez',
            'website' => 'https://www.bmsc.com.bo',
            'industry_code' => 'finance',
            'primary_color' => '#006341',
            'secondary_color' => '#FFD700',
            'logo_filename' => 'mercantil-santa-cruz-logo.png',
            'company_admin' => [
                'first_name' => 'Pablo',
                'last_name' => 'Antelo',
                'email' => 'pablo.antelo@bmsc.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Gabriela',
                    'last_name' => 'Torres',
                    'email' => 'gabriela.torres@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Rodrigo',
                    'last_name' => 'Montaño',
                    'email' => 'rodrigo.montano@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Mariana',
                    'last_name' => 'Cruz',
                    'email' => 'mariana.cruz@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Alberto',
                    'last_name' => 'Bravo',
                    'email' => 'alberto.bravo@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Cecilia',
                    'last_name' => 'Moreno',
                    'email' => 'cecilia.moreno@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Víctor',
                    'last_name' => 'Quiroga',
                    'email' => 'victor.quiroga@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Adriana',
                    'last_name' => 'Soto',
                    'email' => 'adriana.soto@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Leonardo',
                    'last_name' => 'Baldivieso',
                    'email' => 'leonardo.baldivieso@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Viviana',
                    'last_name' => 'Peña',
                    'email' => 'viviana.pena@bmsc.com.bo',
                ],
                [
                    'first_name' => 'Álvaro',
                    'last_name' => 'Velasco',
                    'email' => 'alvaro.velasco@bmsc.com.bo',
                ],
            ],
            'areas' => [
                [
                    'name' => 'Banca Corporativa',
                    'description' => 'Créditos empresariales, financiamiento corporativo, productos especializados',
                ],
                [
                    'name' => 'Banca Personal',
                    'description' => 'Cuentas de ahorro, préstamos personales, tarjetas de crédito',
                ],
                [
                    'name' => 'Operaciones y Tesorería',
                    'description' => 'Procesamiento de transacciones, gestión de efectivo, operaciones cambiarias',
                ],
                [
                    'name' => 'Riesgos y Cumplimiento',
                    'description' => 'Gestión de riesgos financieros, compliance, prevención de lavado de activos',
                ],
                [
                    'name' => 'Tecnología Bancaria',
                    'description' => 'Core bancario, banca digital, seguridad informática, canales electrónicos',
                ],
                [
                    'name' => 'Recursos Humanos',
                    'description' => 'Gestión del talento, capacitación, desarrollo organizacional',
                ],
                [
                    'name' => 'Administración y Finanzas',
                    'description' => 'Contabilidad, presupuestos, asuntos legales, administración general',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando 6 empresas bolivianas GRANDES con datos profesionales...');

        foreach (self::COMPANIES as $companyData) {
            try {
                // 1. Crear Company Admin
                $admin = $this->createUser(
                    $companyData['company_admin']['first_name'],
                    $companyData['company_admin']['last_name'],
                    $companyData['company_admin']['email'],
                );

                // 2. Obtener industry_id
                $industry = CompanyIndustry::where('code', $companyData['industry_code'])->first();
                if (!$industry) {
                    $this->command->error("❌ Industria no encontrada: {$companyData['industry_code']}");
                    continue;
                }

                // 3. Crear Empresa usando CompanyService (dispara CompanyCreated event → auto-crea categorías)
                // Usar company_code fijo del array (determinístico, no genera automáticamente)
                $companyService = app(CompanyService::class);
                $company = $companyService->create([
                    'company_code' => $companyData['company_code'],
                    'name' => $companyData['name'],
                    'legal_name' => $companyData['legal_name'],
                    'description' => $companyData['description'],
                    'support_email' => $companyData['support_email'],
                    'phone' => $companyData['phone'],
                    'website' => $companyData['website'],
                    'contact_address' => $companyData['address'],
                    'contact_city' => $companyData['city'],
                    'contact_state' => $companyData['state'],
                    'contact_country' => 'Bolivia',
                    'contact_postal_code' => $companyData['postal_code'],
                    'tax_id' => $companyData['tax_id'],
                    'legal_representative' => $companyData['legal_rep'],
                    'primary_color' => $companyData['primary_color'],
                    'secondary_color' => $companyData['secondary_color'],
                    'business_hours' => [
                        'monday' => ['open' => '08:30', 'close' => '18:00'],
                        'tuesday' => ['open' => '08:30', 'close' => '18:00'],
                        'wednesday' => ['open' => '08:30', 'close' => '18:00'],
                        'thursday' => ['open' => '08:30', 'close' => '18:00'],
                        'friday' => ['open' => '08:30', 'close' => '17:00'],
                        'saturday' => ['open' => '09:00', 'close' => '13:00'],
                    ],
                    'timezone' => 'America/La_Paz',
                    'status' => 'active',
                    'industry_id' => $industry->id,
                ], $admin);

                $this->command->info("✅ Empresa '{$company->name}' creada con admin: {$admin->email}");

                // 4. Asignar rol COMPANY_ADMIN
                UserRole::create([
                    'user_id' => $admin->id,
                    'role_code' => 'COMPANY_ADMIN',
                    'company_id' => $company->id,
                    'is_active' => true,
                ]);

                // 5. Crear 2 Agentes
                foreach ($companyData['agents'] as $agentData) {
                    $agent = $this->createUser(
                        $agentData['first_name'],
                        $agentData['last_name'],
                        $agentData['email'],
                    );

                    UserRole::create([
                        'user_id' => $agent->id,
                        'role_code' => 'AGENT',
                        'company_id' => $company->id,
                        'is_active' => true,
                    ]);

                    $this->command->info("  └─ Agente creado: {$agent->email}");
                }

                // 6. Crear Áreas para la empresa
                $areasCount = count($companyData['areas']);
                $this->command->info("  ├─ Creando {$areasCount} áreas para la empresa...");
                foreach ($companyData['areas'] as $areaData) {
                    Area::create([
                        'company_id' => $company->id,
                        'name' => $areaData['name'],
                        'description' => $areaData['description'],
                        'is_active' => true,
                    ]);
                    $this->command->info("  │  └─ Área '{$areaData['name']}' creada");
                }

                // 7. Activar areas_enabled en settings de la empresa
                $company->update([
                    'settings' => array_merge(
                        $company->settings ?? [],
                        ['areas_enabled' => true]
                    ),
                ]);
                $this->command->info("  └─ Funcionalidad de áreas activada");

                // 8. Publicar logo si existe
                if (isset($companyData['logo_filename'])) {
                    $this->publishLogo($company, $companyData['logo_filename']);
                }

            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder completado con éxito!');
    }

    /**
     * Publicar logo de empresa (SOLID: Single Responsibility Principle)
     *
     * Copia logo desde resources a storage con estructura determinística:
     * - Origen: app/Features/CompanyManagement/resources/logos/{filename}
     * - Destino: storage/app/public/company-logos/{company_code}/{filename}
     * - URL: asset("storage/company-logos/{company_code}/{filename}")
     *
     * Beneficios:
     * - Sin timestamps → misma URL en cada ejecución
     * - company_code fijo → misma carpeta siempre
     * - Idempotente → no duplica logos en recreaciones de BD
     */
    private function publishLogo(Company $company, string $logoFilename): void
    {
        $sourcePath = $this->getLogoSourcePath($logoFilename);

        if (!$this->validateLogoFile($sourcePath, $logoFilename)) {
            return;
        }

        try {
            $destinationPath = $this->copyLogoToStorage($company, $logoFilename, $sourcePath);
            $this->updateCompanyLogoUrl($company, $destinationPath);

            $this->command->info("  └─ Logo publicado: {$destinationPath}");
        } catch (\Exception $e) {
            $this->command->error("  ❌ Error publicando logo: {$e->getMessage()}");
        }
    }

    /**
     * Obtener ruta completa del logo en resources
     */
    private function getLogoSourcePath(string $logoFilename): string
    {
        return app_path("Features/CompanyManagement/resources/logos/{$logoFilename}");
    }

    /**
     * Validar que el archivo de logo existe
     */
    private function validateLogoFile(string $sourcePath, string $logoFilename): bool
    {
        if (!file_exists($sourcePath)) {
            $this->command->warn("  ⚠️  Logo no encontrado: {$logoFilename}");
            return false;
        }

        return true;
    }

    /**
     * Copiar logo desde resources a storage público (SOLID: Open/Closed Principle)
     * Estructura determinística sin timestamps
     */
    private function copyLogoToStorage(Company $company, string $logoFilename, string $sourcePath): string
    {
        $fileContent = file_get_contents($sourcePath);

        // Estructura: company-logos/{company_code}/{filename}
        $storagePath = "company-logos/{$company->company_code}";

        // Crear directorio si no existe
        if (!Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->makeDirectory($storagePath);
        }

        // Guardar archivo (sin timestamp, siempre el mismo nombre)
        $fullPath = "{$storagePath}/{$logoFilename}";
        Storage::disk('public')->put($fullPath, $fileContent);

        return $fullPath;
    }

    /**
     * Actualizar URL del logo en la empresa
     */
    private function updateCompanyLogoUrl(Company $company, string $storagePath): void
    {
        $logoUrl = asset("storage/{$storagePath}");
        $company->update(['logo_url' => $logoUrl]);
    }

    private function createUser(string $firstName, string $lastName, string $email): User
    {
        $userCode = CodeGenerator::generate('auth.users', CodeGenerator::USER, 'user_code');

        $user = User::create([
            'user_code' => $userCode,
            'email' => $email,
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

        $user->profile()->create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => null,
            'theme' => 'light',
            'language' => 'es',
            'timezone' => 'America/La_Paz',
        ]);

        return $user;
    }

}
