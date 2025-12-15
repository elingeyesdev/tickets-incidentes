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
                'name' => 'Reporte de Error/Bug',
                'description' => 'Algo funciona incorrectamente, comportamiento inesperado, crash, excepción',
            ],
            [
                'name' => 'Solicitud de Funcionalidad',
                'description' => 'Solicitud de nuevas features, mejoras, cambios',
            ],
            [
                'name' => 'Problema de Rendimiento',
                'description' => 'Aplicación lenta, timeouts, recursos agotados',
            ],
            [
                'name' => 'Consulta Técnica',
                'description' => 'Preguntas sobre uso, documentación, integración',
            ],
            [
                'name' => 'Problema de Acceso/Autenticación',
                'description' => 'No puede loguear, credenciales incorrectas, permisos',
            ],
        ],

        'healthcare' => [
            [
                'name' => 'Problema con Cita',
                'description' => 'No puedo agendar, necesito cambiar hora, quiero cancelar',
            ],
            [
                'name' => 'Problema de Acceso al Sistema',
                'description' => 'No puedo entrar a sistema EMR, credenciales no funcionan',
            ],
            [
                'name' => 'Solicitud de Historial Médico',
                'description' => 'Necesito acceso a mi historial, imprimir resultado',
            ],
            [
                'name' => 'Consulta sobre Cobertura',
                'description' => '¿Qué cubre el seguro? ¿Qué medicinas están cubiertas?',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Cobro incorrecto, duplicado, en deuda',
            ],
        ],

        'education' => [
            [
                'name' => 'Problema con Acceso al Curso',
                'description' => 'No puedo entrar a clase virtual, no veo materiales',
            ],
            [
                'name' => 'Consulta sobre Calificaciones',
                'description' => '¿Cuál es mi nota final? ¿Cuándo salen notas?',
            ],
            [
                'name' => 'Solicitud de Documento Académico',
                'description' => 'Necesito certificado de notas, transcripción',
            ],
            [
                'name' => 'Problema Técnico de Plataforma',
                'description' => 'Plataforma lenta, video no carga, error al enviar tarea',
            ],
            [
                'name' => 'Queja sobre Servicio Educativo',
                'description' => 'Maestro desapareció, clase desorganizada, contenido malo',
            ],
        ],

        'finance' => [
            [
                'name' => 'Problema de Cuenta/Inversión',
                'description' => 'Saldo no coincide, desaparición de fondos',
            ],
            [
                'name' => 'Solicitud sobre Póliza/Producto',
                'description' => 'Quiero cambiar de plan, información sobre producto nuevo',
            ],
            [
                'name' => 'Problema de Transacción',
                'description' => 'Transferencia no llegó, retiro rechazado, demora',
            ],
            [
                'name' => 'Reporte de Seguridad/Fraude',
                'description' => 'Veo movimientos sospechosos, creo que me robaron',
            ],
            [
                'name' => 'Consulta sobre Políticas',
                'description' => '¿Cuál es la tasa de interés? ¿Qué comisiones cobran?',
            ],
        ],

        'retail' => [
            [
                'name' => 'Problema con Pedido',
                'description' => 'Pedido no llegó, llegó incompleto, llegó dañado',
            ],
            [
                'name' => 'Problema de Pago',
                'description' => 'Transacción rechazada, cobrado dos veces, dinero aún no refundado',
            ],
            [
                'name' => 'Solicitud de Devolución/Cambio',
                'description' => 'Quiero devolver, cambiar por otro tamaño, no me gusta',
            ],
            [
                'name' => 'Consulta sobre Envío',
                'description' => '¿Dónde está mi pedido? ¿Cuándo llega? ¿Costo del envío?',
            ],
            [
                'name' => 'Queja sobre Calidad del Producto',
                'description' => 'Producto de mala calidad, no como se describe',
            ],
        ],

        'manufacturing' => [
            [
                'name' => 'Problema de Equipo/Máquina',
                'description' => 'Máquina se dañó, línea paró, mantenimiento urgente',
            ],
            [
                'name' => 'Problema de Calidad del Producto',
                'description' => 'Producto fuera de tolerancia, defecto en lote, tasa de rechazo alta',
            ],
            [
                'name' => 'Problema de Cadena de Suministro',
                'description' => 'Proveedor retrasado, materia prima defectuosa, falta stock',
            ],
            [
                'name' => 'Consulta sobre Proceso/Especificación',
                'description' => 'Cómo funciona este proceso? Cuáles son las tolerancias?',
            ],
            [
                'name' => 'Solicitud de Cambio de Proceso',
                'description' => 'Necesito cambiar temperatura, velocidad de línea, parámetros',
            ],
        ],

        'real_estate' => [
            [
                'name' => 'Solicitud de Información de Propiedad',
                'description' => 'Quiero saber propiedades disponibles, área, características',
            ],
            [
                'name' => 'Problema con Arrendamiento/Contrato',
                'description' => 'Disputa sobre cláusulas, renovación, términos no claros',
            ],
            [
                'name' => 'Solicitud de Mantenimiento',
                'description' => 'Reparación de baño, gotera, falla de electricidad, daño',
            ],
            [
                'name' => 'Problema de Facturación/Pago de Renta',
                'description' => 'Renta cobrada incorrectamente, pago no procesó, aumento sin aviso',
            ],
            [
                'name' => 'Consulta sobre Disponibilidad/Términos',
                'description' => 'Cuál es el precio? Hay depósito de garantía? Cuánto es el contrato?',
            ],
        ],

        'hospitality' => [
            [
                'name' => 'Problema con Reservación',
                'description' => 'No puedo hacer reserva online, error en booking, sistema no funciona',
            ],
            [
                'name' => 'Queja sobre Habitación/Servicio',
                'description' => 'Habitación sucia, hay insectos, ruido de huéspedes, servicio lento',
            ],
            [
                'name' => 'Problema de Facturación/Cobro',
                'description' => 'Cobro incorrecto, cargo no autorizado, problema con reembolso',
            ],
            [
                'name' => 'Solicitud de Servicio Especial',
                'description' => 'Quiero room service, late checkout, extra towels, rollaway bed',
            ],
            [
                'name' => 'Problema de Equipo/Infraestructura',
                'description' => 'Baño no funciona, A/C roto, wifi no funciona, agua fría',
            ],
        ],

        'transportation' => [
            [
                'name' => 'Problema de Envío/Entrega',
                'description' => 'Envío no llegó, llegó dañado, se perdió, retraso significativo',
            ],
            [
                'name' => 'Problema de Vehículo',
                'description' => 'Camión descompuesto, falla mecánica, necesita mantenimiento urgente',
            ],
            [
                'name' => 'Consulta de Rastreo',
                'description' => '¿Dónde está mi paquete? ¿Cuándo llega? Estado del envío',
            ],
            [
                'name' => 'Reporte de Conductor',
                'description' => 'Conductor fue grosero, conducción riesgosa, comportamiento inapropiado',
            ],
            [
                'name' => 'Solicitud de Logística',
                'description' => 'Cambiar fecha, agregar parada, cambiar dirección de entrega',
            ],
        ],

        'professional_services' => [
            [
                'name' => 'Problema con Proyecto',
                'description' => 'Retraso, scope creep, comunicación falla',
            ],
            [
                'name' => 'Solicitud de Documentación/Reporte',
                'description' => 'Necesito reporte del proyecto',
            ],
            [
                'name' => 'Problema de Facturación/Cobro',
                'description' => 'Factura incorrecta, disputa de costos',
            ],
            [
                'name' => 'Consulta sobre Progreso/Status',
                'description' => '¿Cómo va el proyecto?',
            ],
            [
                'name' => 'Queja sobre Servicio',
                'description' => 'No estoy satisfecho con el trabajo',
            ],
        ],

        'media' => [
            [
                'name' => 'Solicitud de Contenido/Diseño',
                'description' => 'Necesito banner, video, contenido',
            ],
            [
                'name' => 'Problema con Campaña',
                'description' => 'Campaña no se publicó, error en ejecución',
            ],
            [
                'name' => 'Consulta sobre Disponibilidad/Precios',
                'description' => '¿Hay disponibilidad? ¿Cuál es el precio?',
            ],
            [
                'name' => 'Problema de Facturación/Servicios',
                'description' => 'Cobro incorrecto, servicio no entregado',
            ],
            [
                'name' => 'Queja sobre Calidad',
                'description' => 'Diseño no es lo que pedí, resultado pobre',
            ],
        ],

        'energy' => [
            [
                'name' => 'Incidente de Interrupción del Servicio',
                'description' => 'Se cortó la luz, no hay suministro, apagón en zona',
            ],
            [
                'name' => 'Problema de Equipo/Infraestructura',
                'description' => 'Medidor dañado, instalación defectuosa, mantenimiento urgente',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Consumo no coincide, cobro muy alto, error en cálculo',
            ],
            [
                'name' => 'Reporte de Seguridad',
                'description' => 'Línea suelta, transformador peligroso, peligro de electrocución',
            ],
            [
                'name' => 'Consulta sobre Consumo/Tarifas',
                'description' => 'Cómo bajo consumo? Cuál es mi tarifa? Qué plan es mejor?',
            ],
        ],

        'telecommunications' => [
            [
                'name' => 'Incidente de Red',
                'description' => 'Internet caído, sin señal móvil, servicio completamente interrumpido',
            ],
            [
                'name' => 'Degradación de Servicio',
                'description' => 'Internet muy lento, llamadas cortadas, jitter en video',
            ],
            [
                'name' => 'Solicitud de Activación/Cambio',
                'description' => 'Activar línea móvil, instalar fibra, cambiar a plan superior',
            ],
            [
                'name' => 'Problema de Facturación',
                'description' => 'Cobro incorrecto, cargo duplicado, cargo no autorizado',
            ],
            [
                'name' => 'Consulta sobre Planes/Disponibilidad',
                'description' => 'Qué planes tienen? Hay cobertura en mi zona? Cuál es el mejor plan?',
            ],
        ],

        'food_and_beverage' => [
            [
                'name' => 'Incidente de Producción',
                'description' => 'Línea de producción parada, equipo dañado, mantenimiento urgente',
            ],
            [
                'name' => 'Problema de Calidad del Producto',
                'description' => 'Lote defectuoso, producto fuera de especificación, falla de sellado',
            ],
            [
                'name' => 'Problema de Cadena de Frío/Logística',
                'description' => 'Producto dañado por temperatura, retraso en entrega, pérdida',
            ],
            [
                'name' => 'Incidente de Seguridad Alimentaria',
                'description' => 'Contaminación detectada, alérgeno no declarado, retiro de producto',
            ],
            [
                'name' => 'Solicitud/Consulta sobre Proceso',
                'description' => 'Información sobre proceso, solicitud de cambio de parámetros, ¿cómo funciona X?',
            ],
        ],

        'pharmacy' => [
            [
                'name' => 'Consulta Farmacéutica',
                'description' => 'Puedo tomar esto con ese medicamento? Cuál es la dosis? Efectos secundarios?',
            ],
            [
                'name' => 'Problema de Disponibilidad/Stock',
                'description' => 'No tengo el medicamento, stock agotado, proveedor retrasado',
            ],
            [
                'name' => 'Solicitud de Reposición/Orden',
                'description' => 'Necesito reabastecer medicamentos, hacer pedido de productos',
            ],
            [
                'name' => 'Problema de Facturación/Cobro',
                'description' => 'Discrepancia en inventario, cobro incorrecto, margen incorrecto',
            ],
            [
                'name' => 'Reporte de Cumplimiento/Regulación',
                'description' => 'Auditoría fallida, documentación incompleta, medicamento vencido',
            ],
        ],

        'electronics' => [
            [
                'name' => 'Problema de Hardware',
                'description' => 'Dispositivo no funciona, falla física',
            ],
            [
                'name' => 'Solicitud de Configuración/Instalación',
                'description' => 'Cómo configuro? Instalar drivers, inicializar disco',
            ],
            [
                'name' => 'Solicitud/Problema de Garantía',
                'description' => 'Necesito reparación, garantía vencida, duda sobre cobertura',
            ],
            [
                'name' => 'Problema de Pedido/Envío',
                'description' => 'Pedido no llegó, llegó dañado, unidad defectuosa en caja',
            ],
            [
                'name' => 'Consulta sobre Producto/Especificaciones',
                'description' => 'Cuáles son las especificaciones? Compatible con mi sistema?',
            ],
        ],

        'banking' => [
            [
                'name' => 'Problema de Transacción',
                'description' => 'Transacción falló, dinero no llegó, demora, reversal no aplicó',
            ],
            [
                'name' => 'Problema de Cuenta',
                'description' => 'Saldo incorrecto, acceso denegado, cuenta bloqueada, error de crédito',
            ],
            [
                'name' => 'Reporte de Seguridad/Fraude',
                'description' => 'Veo movimientos sospechosos, creo que me robaron, acceso no autorizado',
            ],
            [
                'name' => 'Solicitud de Servicio Bancario',
                'description' => 'Activar tarjeta nueva, cambiar PIN, solicitar cheques, cambiar límite',
            ],
            [
                'name' => 'Consulta sobre Política/Producto',
                'description' => 'Cuál es la comisión de transferencia? Tasa de interés? Cómo funciona X?',
            ],
        ],

        'supermarket' => [
            [
                'name' => 'Problema de Producto/Compra',
                'description' => 'Producto dañado, precio diferente al mostrador, falta cantidad prometida',
            ],
            [
                'name' => 'Problema de Cadena de Frío',
                'description' => 'Producto perecedero dañado, congelador/refrigerador falla',
            ],
            [
                'name' => 'Solicitud de Información/Disponibilidad',
                'description' => '¿Tienen X producto? Dónde está? ¿En qué piso?',
            ],
            [
                'name' => 'Problema de Facturación/Cobro',
                'description' => 'Cobro incorrecto, doble cobro, descuento no aplicó',
            ],
            [
                'name' => 'Queja sobre Servicio/Tienda',
                'description' => 'Tienda sucia, atención mala, demora en caja, falta de stock',
            ],
        ],

        'veterinary' => [
            [
                'name' => 'Solicitud de Cita/Consulta',
                'description' => 'Quiero agendar cita',
            ],
            [
                'name' => 'Problema con Cita',
                'description' => 'No puedo agendar, cancelaron sin aviso',
            ],
            [
                'name' => 'Urgencia/Emergencia Veterinaria',
                'description' => 'Mascota está enferma, lesión, requiere atención urgente',
            ],
            [
                'name' => 'Consulta sobre Historial/Medicamento',
                'description' => 'Qué vacunas tiene? Qué medicamento darle? Cuándo vuelvo?',
            ],
            [
                'name' => 'Solicitud de Suministros/Medicamentos',
                'description' => 'Necesito alimento especial, medicamento de prescripción',
            ],
        ],

        'insurance' => [
            [
                'name' => 'Consulta sobre Cobertura/Póliza',
                'description' => '¿Qué está cubierto? ¿Cuál es mi beneficio? ¿Límite de cobertura?',
            ],
            [
                'name' => 'Solicitud/Cambio de Póliza',
                'description' => 'Cambiar beneficiario, aumentar cobertura, cambiar de plan',
            ],
            [
                'name' => 'Problema de Reclamación',
                'description' => 'Reclamación rechazada, demora en pago, documentación falta, disputa',
            ],
            [
                'name' => 'Problema de Facturación/Pago Premium',
                'description' => 'Cobro incorrecto, prima no procesó, cargas duplicadas',
            ],
            [
                'name' => 'Reporte de Cumplimiento',
                'description' => 'Auditoría, actualizar información KYC, falta documentación',
            ],
        ],

        'agriculture' => [
            [
                'name' => 'Problema de Equipo/Maquinaria',
                'description' => 'Tractor dañado, cosechadora falla, bomba no funciona',
            ],
            [
                'name' => 'Solicitud de Suministros/Insumos',
                'description' => 'Semillas, fertilizantes, medicinas animales, alimento',
            ],
            [
                'name' => 'Problema de Cultivo/Cosecha',
                'description' => 'Plaga, enfermedad, sequía, inundación, baja producción',
            ],
            [
                'name' => 'Problema de Ganado/Salud Animal',
                'description' => 'Animal enfermo, epizootia, mortalidad alta, problema reproductivo',
            ],
            [
                'name' => 'Consulta sobre Técnica/Proceso',
                'description' => 'Cuándo sembrar? Qué variedad es mejor? Cómo combatir plaga?',
            ],
        ],

        'government' => [
            [
                'name' => 'Solicitud de Trámite/Servicio',
                'description' => 'Sacar cédula, licencia, permiso',
            ],
            [
                'name' => 'Solicitud de Documento/Certificado',
                'description' => 'Certificado de no antecedentes, acta, constancia',
            ],
            [
                'name' => 'Consulta sobre Requisitos/Proceso',
                'description' => 'Qué documentos necesito? Cómo hago el trámite? Cuánto cuesta?',
            ],
            [
                'name' => 'Queja/Reclamo',
                'description' => 'Funcionario fue grosero, negaron injustamente, servicio fue pobre',
            ],
            [
                'name' => 'Problema de Acceso a Sistema/Portal',
                'description' => 'No puedo entrar al portal, error al subir documento, sistema lento',
            ],
        ],

        'non_profit' => [
            [
                'name' => 'Consulta sobre Programa/Servicios',
                'description' => '¿Qué hacen ustedes? ¿Cómo puedo acceder?',
            ],
            [
                'name' => 'Solicitud de Voluntariado',
                'description' => 'Quiero ser voluntario, cómo me uno?',
            ],
            [
                'name' => 'Solicitud de Donación',
                'description' => 'Quiero donar, cómo hago?',
            ],
            [
                'name' => 'Consulta sobre Beneficiario',
                'description' => '¿Mi hijo sigue en programa? ¿Qué necesito llevar?',
            ],
            [
                'name' => 'Queja/Feedback',
                'description' => 'Servicio fue malo, no me ayudaron, retroalimentación',
            ],
        ],

        'construction' => [
            [
                'name' => 'Problema de Proyecto/Cronograma',
                'description' => 'Retraso, cambio de diseño, alcance no claro, conflicto',
            ],
            [
                'name' => 'Problema de Contratista/Proveedor',
                'description' => 'Contratista incumple trabajo, proveedor retrasado, material defectuoso',
            ],
            [
                'name' => 'Solicitud de Cambio/Variación',
                'description' => 'Cambio de diseño, agregar elemento, cambiar material',
            ],
            [
                'name' => 'Incidente de Seguridad Laboral',
                'description' => 'Accidente en obra, peligro identificado, falta de cumplimiento de normas',
            ],
            [
                'name' => 'Problema de Calidad/Inspección',
                'description' => 'Trabajo no cumple estándar, falla de construcción, acabado pobre',
            ],
        ],

        'environment' => [
            [
                'name' => 'Solicitud de Proyecto/Iniciativa',
                'description' => 'Quiero iniciar proyecto de reforestación, sostenibilidad',
            ],
            [
                'name' => 'Consulta sobre Cumplimiento Ambiental',
                'description' => '¿Qué regulaciones aplican? ¿Cómo certificarme?',
            ],
            [
                'name' => 'Solicitud de Servicio de Reciclaje',
                'description' => 'Recolección de residuos, clasificación',
            ],
            [
                'name' => 'Problema de Gestión de Residuos',
                'description' => 'Residuos no fueron recolectados, contaminación',
            ],
            [
                'name' => 'Reporte/Incidente Ambiental',
                'description' => 'Contaminación detectada, derrame, violación ambiental',
            ],
        ],

        'other' => [
            [
                'name' => 'Problema General',
                'description' => 'Algo no funciona',
            ],
            [
                'name' => 'Solicitud General',
                'description' => 'Necesito que hagan algo',
            ],
            [
                'name' => 'Consulta General',
                'description' => 'Necesito información',
            ],
            [
                'name' => 'Queja/Feedback',
                'description' => 'No estoy satisfecho',
            ],
            [
                'name' => 'Reporte General',
                'description' => 'Quiero reportar algo',
            ],
        ],
    ];
}
