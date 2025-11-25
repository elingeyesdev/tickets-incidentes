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
                'name' => 'Bug Report',
                'description' => 'Reportes de errores, fallos y comportamientos inesperados en la aplicación',
            ],
            [
                'name' => 'Feature Request',
                'description' => 'Solicitudes de nuevas funcionalidades y mejoras al sistema',
            ],
            [
                'name' => 'Performance Issue',
                'description' => 'Problemas de rendimiento, velocidad y optimización',
            ],
            [
                'name' => 'Account & Access',
                'description' => 'Problemas de autenticación, permisos y acceso a la plataforma',
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Soporte técnico general e instalación',
            ],
        ],

        'healthcare' => [
            [
                'name' => 'Patient Support',
                'description' => 'Consultas y soporte directo para pacientes',
            ],
            [
                'name' => 'Appointment Issue',
                'description' => 'Problemas con citas, reprogramación o cancelaciones',
            ],
            [
                'name' => 'Medical Records',
                'description' => 'Solicitudes de acceso o actualización de historiales médicos',
            ],
            [
                'name' => 'System Access',
                'description' => 'Problemas de acceso al sistema médico y credenciales',
            ],
            [
                'name' => 'Billing & Insurance',
                'description' => 'Consultas sobre facturación, cobros e seguros',
            ],
        ],

        'education' => [
            [
                'name' => 'Course Issue',
                'description' => 'Problemas con acceso a cursos, materiales o plataforma de aprendizaje',
            ],
            [
                'name' => 'Grade & Assessment',
                'description' => 'Consultas sobre calificaciones, evaluaciones y resultados académicos',
            ],
            [
                'name' => 'Account Access',
                'description' => 'Problemas de acceso a cuenta de estudiante o docente',
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Soporte técnico para herramientas educativas',
            ],
            [
                'name' => 'Administrative Request',
                'description' => 'Solicitudes de documentación académica, certificados y trámites',
            ],
        ],

        'finance' => [
            [
                'name' => 'Account Issue',
                'description' => 'Problemas con cuentas, saldos y movimientos',
            ],
            [
                'name' => 'Transaction Problem',
                'description' => 'Problemas con transacciones, transferencias o pagos',
            ],
            [
                'name' => 'Security Concern',
                'description' => 'Reportes de actividad sospechosa o problemas de seguridad',
            ],
            [
                'name' => 'Compliance & Regulatory',
                'description' => 'Consultas sobre cumplimiento normativo y regulaciones',
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Soporte técnico y problemas con plataformas de banca digital',
            ],
        ],

        'retail' => [
            [
                'name' => 'Order Issue',
                'description' => 'Problemas con pedidos, devoluciones o modificaciones',
            ],
            [
                'name' => 'Payment Problem',
                'description' => 'Problemas de pago, reembolsos o transacciones fallidas',
            ],
            [
                'name' => 'Shipping & Delivery',
                'description' => 'Consultas sobre envío, seguimiento y entrega de productos',
            ],
            [
                'name' => 'Product Return',
                'description' => 'Solicitudes de devolución, cambio o reemplazo de productos',
            ],
            [
                'name' => 'Account Access',
                'description' => 'Problemas de acceso a cuenta, contraseña u perfil',
            ],
        ],

        'manufacturing' => [
            [
                'name' => 'Equipment Issue',
                'description' => 'Problemas y mantenimiento de equipos e maquinaria',
            ],
            [
                'name' => 'Production Delay',
                'description' => 'Reportes de retrasos en producción o cuellos de botella',
            ],
            [
                'name' => 'Quality Problem',
                'description' => 'Problemas de calidad, defectos o control de calidad',
            ],
            [
                'name' => 'Supply Chain',
                'description' => 'Consultas sobre proveedores, materias primas y logística',
            ],
            [
                'name' => 'Safety Concern',
                'description' => 'Reportes de problemas de seguridad e higiene industrial',
            ],
        ],

        'real_estate' => [
            [
                'name' => 'Property Inquiry',
                'description' => 'Consultas sobre propiedades, disponibilidad y características',
            ],
            [
                'name' => 'Lease & Contract',
                'description' => 'Consultas sobre contratos, términos de arrendamiento',
            ],
            [
                'name' => 'Maintenance Request',
                'description' => 'Solicitudes de reparación y mantenimiento de propiedades',
            ],
            [
                'name' => 'Billing Issue',
                'description' => 'Problemas con rentas, pagos y facturación',
            ],
            [
                'name' => 'Document Request',
                'description' => 'Solicitud de documentos, certificados y permisos',
            ],
        ],

        'hospitality' => [
            [
                'name' => 'Reservation Issue',
                'description' => 'Problemas con reservaciones, cancelaciones o modificaciones',
            ],
            [
                'name' => 'Room & Service Complaint',
                'description' => 'Quejas sobre calidad de habitación, limpieza y servicio',
            ],
            [
                'name' => 'Billing Problem',
                'description' => 'Problemas con cargos, facturas o refunds',
            ],
            [
                'name' => 'Maintenance Request',
                'description' => 'Reportes de daños, averías o necesidades de reparación',
            ],
            [
                'name' => 'Guest Support',
                'description' => 'Soporte general y consultas de huéspedes durante su estadía',
            ],
        ],

        'transportation' => [
            [
                'name' => 'Shipment Tracking',
                'description' => 'Consultas sobre ubicación y estado de envíos',
            ],
            [
                'name' => 'Delivery Problem',
                'description' => 'Problemas de entrega, retrasos o daños en tránsito',
            ],
            [
                'name' => 'Vehicle Issue',
                'description' => 'Problemas mecánicos y mantenimiento de vehículos',
            ],
            [
                'name' => 'Driver Concern',
                'description' => 'Reportes sobre comportamiento de conductores y seguridad',
            ],
            [
                'name' => 'Billing & Invoice',
                'description' => 'Consultas sobre facturas, pagos y costos de transporte',
            ],
        ],

        'professional_services' => [
            [
                'name' => 'Project Issue',
                'description' => 'Problemas con proyectos, cronogramas y alcance de trabajo',
            ],
            [
                'name' => 'Document & Report',
                'description' => 'Solicitudes de documentación, reportes e informes',
            ],
            [
                'name' => 'Billing Dispute',
                'description' => 'Disputas por facturas, costos y términos de pago',
            ],
            [
                'name' => 'Compliance Question',
                'description' => 'Consultas sobre normas, regulaciones y cumplimiento',
            ],
            [
                'name' => 'Account Access',
                'description' => 'Problemas de acceso a plataformas y sistemas de gestión',
            ],
        ],

        'media' => [
            [
                'name' => 'Campaign Issue',
                'description' => 'Problemas con campañas publicitarias y ejecución',
            ],
            [
                'name' => 'Content Request',
                'description' => 'Solicitudes de creación, edición o publicación de contenido',
            ],
            [
                'name' => 'Design & Creative',
                'description' => 'Solicitudes de diseño, creatividad y material visual',
            ],
            [
                'name' => 'Billing Problem',
                'description' => 'Problemas con facturas, servicios y pagos',
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Soporte técnico para plataformas de publicación',
            ],
        ],

        'energy' => [
            [
                'name' => 'Service Outage',
                'description' => 'Reportes de cortes de servicio, apagones y falta de suministro',
            ],
            [
                'name' => 'Billing Dispute',
                'description' => 'Disputas por consumo, facturas y cargos',
            ],
            [
                'name' => 'Safety Concern',
                'description' => 'Reportes de peligros, riesgos y problemas de seguridad',
            ],
            [
                'name' => 'Equipment Problem',
                'description' => 'Problemas con medidores, instalaciones y equipos',
            ],
            [
                'name' => 'Maintenance Request',
                'description' => 'Solicitudes de mantenimiento preventivo y correctivo',
            ],
        ],

        'agriculture' => [
            [
                'name' => 'Equipment Issue',
                'description' => 'Problemas con maquinaria agrícola y equipos',
            ],
            [
                'name' => 'Supply Order',
                'description' => 'Solicitudes de semillas, fertilizantes y suministros',
            ],
            [
                'name' => 'Crop & Livestock Problem',
                'description' => 'Problemas de plagas, enfermedades y salud animal',
            ],
            [
                'name' => 'Pricing Dispute',
                'description' => 'Consultas sobre precios, contratos y términos comerciales',
            ],
            [
                'name' => 'Technical Support',
                'description' => 'Soporte para sistemas de riego, drones y tecnología agrícola',
            ],
        ],

        'government' => [
            [
                'name' => 'Service Request',
                'description' => 'Solicitudes de servicios públicos y trámites administrativos',
            ],
            [
                'name' => 'Document Request',
                'description' => 'Solicitudes de documentación, certificados y permisos',
            ],
            [
                'name' => 'Complaint',
                'description' => 'Quejas sobre servicios, infraestructura o funcionarios',
            ],
            [
                'name' => 'Account Access',
                'description' => 'Problemas de acceso a portales y sistemas en línea',
            ],
            [
                'name' => 'Administrative',
                'description' => 'Consultas administrativas y procedimientos oficiales',
            ],
        ],

        'non_profit' => [
            [
                'name' => 'Donation & Contribution',
                'description' => 'Consultas sobre donaciones, contribuciones y patrocinios',
            ],
            [
                'name' => 'Volunteer Inquiry',
                'description' => 'Consultas sobre voluntariado y participación en programas',
            ],
            [
                'name' => 'Program Support',
                'description' => 'Soporte para programas, beneficiarios y actividades',
            ],
            [
                'name' => 'Event Support',
                'description' => 'Apoyo para organización y realización de eventos',
            ],
            [
                'name' => 'Account Access',
                'description' => 'Problemas de acceso a plataformas y sistemas',
            ],
        ],

        'other' => [
            [
                'name' => 'General Support',
                'description' => 'Soporte general sobre productos y servicios',
            ],
            [
                'name' => 'Question',
                'description' => 'Preguntas generales sobre operaciones y procesos',
            ],
            [
                'name' => 'Complaint',
                'description' => 'Quejas y retroalimentación general',
            ],
            [
                'name' => 'Request',
                'description' => 'Solicitudes diversas no clasificadas en otras categorías',
            ],
            [
                'name' => 'Technical Issue',
                'description' => 'Problemas técnicos varios',
            ],
        ],
    ];
}
