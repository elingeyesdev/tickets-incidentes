<?php

declare(strict_types=1);

namespace App\Features\TicketManagement\Data;

/**
 * Mapeo de categorías de tickets por defecto según industry type
 *
 * Define 5 categorías específicas para cada tipo de industria (16 industrias = 80 categorías).
 * Usado por CreateDefaultCategoriesListener para auto-crear categorías cuando se crea una empresa.
 */
final class DefaultCategoriesByIndustry
{
    /**
     * Obtener las 5 categorías por defecto para un código de industria
     *
     * @param string $industryCode Código de industria (ej: 'technology', 'healthcare')
     * @return array Array de categorías con 'name' y 'description'
     */
    public static function get(string $industryCode): array
    {
        return self::CATEGORIES_MAP[$industryCode] ?? self::CATEGORIES_MAP['other'];
    }

    /**
     * Mapeo completo de categorías por industria
     */
    private const CATEGORIES_MAP = [
        'technology' => [
            [
                'name' => 'Reporte de Error',
                'description' => 'Reportes de errores, fallos y comportamientos inesperados en la aplicación',
            ],
            [
                'name' => 'Solicitud de Funcionalidad',
                'description' => 'Solicitudes de nuevas funcionalidades y mejoras al sistema',
            ],
            [
                'name' => 'Problema de Rendimiento',
                'description' => 'Problemas de rendimiento, velocidad y optimización',
            ],
            [
                'name' => 'Cuenta y Acceso',
                'description' => 'Problemas de autenticación, permisos y acceso a la plataforma',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte técnico general e instalación',
            ],
        ],

        'healthcare' => [
            [
                'name' => 'Atención al Paciente',
                'description' => 'Consultas y soporte directo para pacientes',
            ],
            [
                'name' => 'Problema con Citas',
                'description' => 'Problemas con citas, reprogramación o cancelaciones',
            ],
            [
                'name' => 'Historial Médico',
                'description' => 'Solicitudes de acceso o actualización de historiales médicos',
            ],
            [
                'name' => 'Acceso al Sistema',
                'description' => 'Problemas de acceso al sistema médico y credenciales',
            ],
            [
                'name' => 'Facturación y Seguros',
                'description' => 'Consultas sobre facturación, cobros e seguros',
            ],
        ],

        'education' => [
            [
                'name' => 'Problema con Curso',
                'description' => 'Problemas con acceso a cursos, materiales o plataforma de aprendizaje',
            ],
            [
                'name' => 'Calificaciones y Evaluaciones',
                'description' => 'Consultas sobre calificaciones, evaluaciones y resultados académicos',
            ],
            [
                'name' => 'Acceso a la Cuenta',
                'description' => 'Problemas de acceso a cuenta de estudiante o docente',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte técnico para herramientas educativas',
            ],
            [
                'name' => 'Solicitud Administrativa',
                'description' => 'Solicitudes de documentación académica, certificados y trámites',
            ],
        ],

        'finance' => [
            [
                'name' => 'Problema de Cuenta',
                'description' => 'Problemas con cuentas, saldos y movimientos',
            ],
            [
                'name' => 'Problema de Transacción',
                'description' => 'Problemas con transacciones, transferencias o pagos',
            ],
            [
                'name' => 'Problema de Seguridad',
                'description' => 'Reportes de actividad sospechosa o problemas de seguridad',
            ],
            [
                'name' => 'Cumplimiento y Regulación',
                'description' => 'Consultas sobre cumplimiento normativo y regulaciones',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte técnico y problemas con plataformas de banca digital',
            ],
        ],

        'retail' => [
            [
                'name' => 'Problema con Pedido',
                'description' => 'Problemas con pedidos, devoluciones o modificaciones',
            ],
            [
                'name' => 'Problema de Pago',
                'description' => 'Problemas de pago, reembolsos o transacciones fallidas',
            ],
            [
                'name' => 'Envío y Entrega',
                'description' => 'Consultas sobre envío, seguimiento y entrega de productos',
            ],
            [
                'name' => 'Devolución de Producto',
                'description' => 'Solicitudes de devolución, cambio o reemplazo de productos',
            ],
            [
                'name' => 'Acceso a la Cuenta',
                'description' => 'Problemas de acceso a cuenta, contraseña u perfil',
            ],
        ],

        'manufacturing' => [
            [
                'name' => 'Problema de Equipo',
                'description' => 'Problemas y mantenimiento de equipos e maquinaria',
            ],
            [
                'name' => 'Retraso en Producción',
                'description' => 'Reportes de retrasos en producción o cuellos de botella',
            ],
            [
                'name' => 'Problema de Calidad',
                'description' => 'Problemas de calidad, defectos o control de calidad',
            ],
            [
                'name' => 'Cadena de Suministro',
                'description' => 'Consultas sobre proveedores, materias primas y logística',
            ],
            [
                'name' => 'Problema de Seguridad',
                'description' => 'Reportes de problemas de seguridad e higiene industrial',
            ],
        ],

        'real_estate' => [
            [
                'name' => 'Consulta de Propiedad',
                'description' => 'Consultas sobre propiedades, disponibilidad y características',
            ],
            [
                'name' => 'Arrendamiento y Contrato',
                'description' => 'Consultas sobre contratos, términos de arrendamiento',
            ],
            [
                'name' => 'Solicitud de Mantenimiento',
                'description' => 'Solicitudes de reparación y mantenimiento de propiedades',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Problemas con rentas, pagos y facturación',
            ],
            [
                'name' => 'Solicitud de Documento',
                'description' => 'Solicitud de documentos, certificados y permisos',
            ],
        ],

        'hospitality' => [
            [
                'name' => 'Problema de Reservación',
                'description' => 'Problemas con reservaciones, cancelaciones o modificaciones',
            ],
            [
                'name' => 'Queja de Habitación y Servicio',
                'description' => 'Quejas sobre calidad de habitación, limpieza y servicio',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Problemas con cargos, facturas o refunds',
            ],
            [
                'name' => 'Solicitud de Mantenimiento',
                'description' => 'Reportes de daños, averías o necesidades de reparación',
            ],
            [
                'name' => 'Atención al Huésped',
                'description' => 'Soporte general y consultas de huéspedes durante su estadía',
            ],
        ],

        'transportation' => [
            [
                'name' => 'Rastreo de Envío',
                'description' => 'Consultas sobre ubicación y estado de envíos',
            ],
            [
                'name' => 'Problema de Entrega',
                'description' => 'Problemas de entrega, retrasos o daños en tránsito',
            ],
            [
                'name' => 'Problema de Vehículo',
                'description' => 'Problemas mecánicos y mantenimiento de vehículos',
            ],
            [
                'name' => 'Reporte de Conductor',
                'description' => 'Reportes sobre comportamiento de conductores y seguridad',
            ],
            [
                'name' => 'Facturación',
                'description' => 'Consultas sobre facturas, pagos y costos de transporte',
            ],
        ],

        'professional_services' => [
            [
                'name' => 'Problema de Proyecto',
                'description' => 'Problemas con proyectos, cronogramas y alcance de trabajo',
            ],
            [
                'name' => 'Documentos y Reportes',
                'description' => 'Solicitudes de documentación, reportes e informes',
            ],
            [
                'name' => 'Disputa de Facturación',
                'description' => 'Disputas por facturas, costos y términos de pago',
            ],
            [
                'name' => 'Consulta de Cumplimiento',
                'description' => 'Consultas sobre normas, regulaciones y cumplimiento',
            ],
            [
                'name' => 'Acceso a la Cuenta',
                'description' => 'Problemas de acceso a plataformas y sistemas de gestión',
            ],
        ],

        'media' => [
            [
                'name' => 'Problema de Campaña',
                'description' => 'Problemas con campañas publicitarias y ejecución',
            ],
            [
                'name' => 'Solicitud de Contenido',
                'description' => 'Solicitudes de creación, edición o publicación de contenido',
            ],
            [
                'name' => 'Diseño y Creatividad',
                'description' => 'Solicitudes de diseño, creatividad y material visual',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Problemas con facturas, servicios y pagos',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte técnico para plataformas de publicación',
            ],
        ],

        'energy' => [
            [
                'name' => 'Interrupción del Servicio',
                'description' => 'Reportes de cortes de servicio, apagones y falta de suministro',
            ],
            [
                'name' => 'Disputa de Facturación',
                'description' => 'Disputas por consumo, facturas y cargos',
            ],
            [
                'name' => 'Problema de Seguridad',
                'description' => 'Reportes de peligros, riesgos y problemas de seguridad',
            ],
            [
                'name' => 'Problema de Equipo',
                'description' => 'Problemas con medidores, instalaciones y equipos',
            ],
            [
                'name' => 'Solicitud de Mantenimiento',
                'description' => 'Solicitudes de mantenimiento preventivo y correctivo',
            ],
        ],

        'telecommunications' => [
            [
                'name' => 'Incidente de Red',
                'description' => 'Cortes de red, torres, conectividad móvil o fija',
            ],
            [
                'name' => 'Degradación de Servicio',
                'description' => 'Latencia, baja velocidad, jitter y problemas de calidad de servicio',
            ],
            [
                'name' => 'Instalación y Activación',
                'description' => 'Solicitudes de activación de líneas, migraciones o instalaciones',
            ],
            [
                'name' => 'Facturación y Planes',
                'description' => 'Consultas sobre facturas, cargos y cambios de plan',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte técnico general para equipos y servicios de telecomunicaciones',
            ],
        ],

        'food_and_beverage' => [
            [
                'name' => 'Problema de Producción',
                'description' => 'Incidentes en líneas de producción, paro de planta o control de procesos',
            ],
            [
                'name' => 'Control de Calidad',
                'description' => 'Problemas de calidad del producto, devoluciones o inspecciones',
            ],
            [
                'name' => 'Logística y Distribución',
                'description' => 'Gestión de inventarios, cadena de frío y distribución de productos',
            ],
            [
                'name' => 'Seguridad Alimentaria',
                'description' => 'Incidentes relacionados con inocuidad, contaminaciones o retiros de producto',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte a sistemas productivos y maquinarias de planta',
            ],
        ],

        'pharmacy' => [
            [
                'name' => 'Atención Farmacéutica',
                'description' => 'Consultas sobre dispensación, disponibilidad de medicamentos y recomendaciones',
            ],
            [
                'name' => 'Gestión de Sucursales',
                'description' => 'Operaciones de puntos de venta, horarios y stock',
            ],
            [
                'name' => 'Cumplimiento y Regulación',
                'description' => 'Consultas sobre normativas sanitarias y farmacovigilancia',
            ],
            [
                'name' => 'Cadena de Suministro',
                'description' => 'Problemas con proveedores, logística y entrega de medicamentos',
            ],
            [
                'name' => 'Facturación y Pagos',
                'description' => 'Consultas sobre facturación, reembolsos y cobros',
            ],
        ],

        'electronics' => [
            [
                'name' => 'Problema de Hardware',
                'description' => 'Fallas en equipos, garantías y devoluciones',
            ],
            [
                'name' => 'Configuración e Instalación',
                'description' => 'Asistencia para montaje, drivers y puesta en marcha',
            ],
            [
                'name' => 'Garantía y Reparación',
                'description' => 'Solicitudes de reparación, RMA y cobertura de garantía',
            ],
            [
                'name' => 'Pedido y Envío',
                'description' => 'Consultas sobre pedidos, tracking y devoluciones',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte general para productos electrónicos y periféricos',
            ],
        ],

        'banking' => [
            [
                'name' => 'Operaciones Bancarias',
                'description' => 'Transacciones, procesamiento y servicios de cuentas',
            ],
            [
                'name' => 'Problemas de Transacción',
                'description' => 'Errores en transferencias, cargos incorrectos o fallos de pago',
            ],
            [
                'name' => 'Seguridad y Fraude',
                'description' => 'Reportes de actividad sospechosa, fraudes y accesos no autorizados',
            ],
            [
                'name' => 'Cumplimiento y Regulación',
                'description' => 'Consultas sobre KYC, cumplimiento normativo y auditorías',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte para plataformas bancarias y servicios digitales',
            ],
        ],

        'supermarket' => [
            [
                'name' => 'Operaciones de Tienda',
                'description' => 'Gestión de sucursales, inventarios y atención en tienda',
            ],
            [
                'name' => 'Cadena de Frío y Perecibles',
                'description' => 'Incidentes relacionados con perecibles y almacenamiento en frío',
            ],
            [
                'name' => 'Pedido y Logística',
                'description' => 'Reposición, distribución y logística de almacén',
            ],
            [
                'name' => 'Promociones y Precios',
                'description' => 'Consultas sobre ofertas, precios y promociones',
            ],
            [
                'name' => 'Atención al Cliente',
                'description' => 'Consultas de clientes, devoluciones y quejas',
            ],
        ],

        'veterinary' => [
            [
                'name' => 'Citas y Atención',
                'description' => 'Agendamiento y consultas de atención veterinaria',
            ],
            [
                'name' => 'Suministros y Medicamentos',
                'description' => 'Disponibilidad y dispensación de productos veterinarios',
            ],
            [
                'name' => 'Urgencias',
                'description' => 'Consultas por emergencias y atención prioritaria',
            ],
            [
                'name' => 'Historial Médico Animal',
                'description' => 'Acceso y actualización de historiales médicos de mascotas',
            ],
            [
                'name' => 'Facturación y Pagos',
                'description' => 'Consultas sobre facturas, pagos y seguros veterinarios',
            ],
        ],

        'beverage' => [
            [
                'name' => 'Control de Calidad',
                'description' => 'Pruebas de calidad, lotes y cumplimiento de estándares',
            ],
            [
                'name' => 'Producción y Planta',
                'description' => 'Incidentes de planta, procesos y mantenimiento',
            ],
            [
                'name' => 'Distribución y Logística',
                'description' => 'Envíos, almacenamiento y logística de bebidas',
            ],
            [
                'name' => 'Marketing y Marca',
                'description' => 'Promociones, eventos y gestión de marca',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte a maquinaria y sistemas de producción',
            ],
        ],

        'agriculture' => [
            [
                'name' => 'Problema de Equipo',
                'description' => 'Problemas con maquinaria agrícola y equipos',
            ],
            [
                'name' => 'Pedido de Suministros',
                'description' => 'Solicitudes de semillas, fertilizantes y suministros',
            ],
            [
                'name' => 'Problema de Cultivos y Ganado',
                'description' => 'Problemas de plagas, enfermedades y salud animal',
            ],
            [
                'name' => 'Disputa de Precios',
                'description' => 'Consultas sobre precios, contratos y términos comerciales',
            ],
            [
                'name' => 'Soporte Técnico',
                'description' => 'Soporte para sistemas de riego, drones y tecnología agrícola',
            ],
        ],

        'government' => [
            [
                'name' => 'Solicitud de Servicio',
                'description' => 'Solicitudes de servicios públicos y trámites administrativos',
            ],
            [
                'name' => 'Solicitud de Documento',
                'description' => 'Solicitudes de documentación, certificados y permisos',
            ],
            [
                'name' => 'Queja',
                'description' => 'Quejas sobre servicios, infraestructura o funcionarios',
            ],
            [
                'name' => 'Acceso a la Cuenta',
                'description' => 'Problemas de acceso a portales y sistemas en línea',
            ],
            [
                'name' => 'Administrativo',
                'description' => 'Consultas administrativas y procedimientos oficiales',
            ],
        ],

        'non_profit' => [
            [
                'name' => 'Donación y Contribución',
                'description' => 'Consultas sobre donaciones, contribuciones y patrocinios',
            ],
            [
                'name' => 'Consulta de Voluntariado',
                'description' => 'Consultas sobre voluntariado y participación en programas',
            ],
            [
                'name' => 'Soporte de Programa',
                'description' => 'Soporte para programas, beneficiarios y actividades',
            ],
            [
                'name' => 'Soporte de Evento',
                'description' => 'Apoyo para organización y realización de eventos',
            ],
            [
                'name' => 'Acceso a la Cuenta',
                'description' => 'Problemas de acceso a plataformas y sistemas',
            ],
        ],

        'other' => [
            [
                'name' => 'Soporte General',
                'description' => 'Soporte general sobre productos y servicios',
            ],
            [
                'name' => 'Pregunta',
                'description' => 'Preguntas generales sobre operaciones y procesos',
            ],
            [
                'name' => 'Queja',
                'description' => 'Quejas y retroalimentación general',
            ],
            [
                'name' => 'Solicitud',
                'description' => 'Solicitudes diversas no clasificadas en otras categorías',
            ],
            [
                'name' => 'Problema Técnico',
                'description' => 'Problemas técnicos varios',
            ],
        ],
    ];
}
