<?php

namespace App\Features\CompanyManagement\Database\Seeders;

use App\Features\CompanyManagement\Models\Company;
use App\Features\CompanyManagement\Models\CompanyIndustry;
use App\Features\CompanyManagement\Services\CompanyService;
use App\Features\ContentManagement\Models\HelpCenterArticle;
use App\Features\ContentManagement\Models\ArticleCategory;
use App\Features\UserManagement\Models\User;
use App\Features\UserManagement\Models\UserRole;
use App\Shared\Enums\UserStatus;
use App\Shared\Helpers\CodeGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Real Bolivian Companies Seeder
 *
 * Crea 5 empresas bolivianas reales con:
 * - 1 Company Admin por empresa
 * - 2 Agentes por empresa
 * - Todos con contraseña: mklmklmkl
 * - industry_id asignado correctamente
 * - 3 artículos de Help Center por empresa
 *
 * Empresas:
 * 1. PIL Andina - Productos Lácteos
 * 2. Banco Fassil - Servicios Financieros
 * 3. YPFB - Petróleo y Gas
 * 4. Tigo - Telecomunicaciones
 * 5. Cervecería Boliviana Nacional - Bebidas
 */
class RealBolivianCompaniesSeeder extends Seeder
{
    private const PASSWORD = 'mklmklmkl';

    private const COMPANIES = [
        [
            'name' => 'PIL Andina S.A.',
            'legal_name' => 'PIL Andina S.A. - Productora Integral Láctea',
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
            ],
            'articles' => [
                [
                    'title' => 'Cómo reportar problemas con productos PIL',
                    'excerpt' => 'Guía paso a paso para reportar problemas de calidad o defectos en nuestros productos',
                    'content' => "## Reportar Problemas con Productos PIL\n\n### Paso 1: Información del Producto\nAntes de reportar, ten a mano:\n- Número de lote del producto\n- Fecha de vencimiento\n- Descripción detallada del problema\n- Fecha de compra\n\n### Paso 2: Contacto Disponible\nPuedes reportar de las siguientes formas:\n1. **Teléfono**: +591 44260164\n2. **Email**: soporte@pilandina.com.bo\n3. **Portal Web**: https://pilandina.com.bo\n\n### Paso 3: Información Requerida\nNos proporcionarás:\n- Tus datos de contacto completos\n- Descripción del problema\n- Fotos del producto (si es posible)\n- Comprobante de compra\n\n### Paso 4: Seguimiento\nTe proporcionaremos un número de ticket para seguimiento del caso.\nTiempo de respuesta: 24-48 horas hábiles.\n\n## Tipos de Problemas Comunes\n\n- **Defecto de envase**: Fugas o daños en la presentación\n- **Problemas de sabor**: Cambio anormal en sabor u olor\n- **Textura anormal**: Separación o cambios físicos\n- **Defectos de empaque**: Etiquetas dañadas o información incorrecta",
                ],
                [
                    'title' => 'Preguntas frecuentes sobre productos PIL',
                    'excerpt' => 'Respuestas a las preguntas más comunes sobre nuestros productos lácteos',
                    'content' => "## Preguntas Frecuentes PIL\n\n### ¿Cuál es la vida útil de los productos PIL?\n\nNuestros productos tienen diferentes fechas de vencimiento según el tipo:\n- **Leche fresca**: 7-10 días refrigerada\n- **Leche larga vida**: 6 meses sin refrigeración\n- **Yogur**: 30 días refrigerado\n- **Quesos**: 60-90 días refrigerados\n\n### ¿Cómo almacenar correctamente los productos?\n\n1. Mantener en lugar fresco y seco\n2. Refrigerar inmediatamente después de comprar\n3. No exponer a luz solar directa\n4. Respetar fechas de vencimiento\n\n### ¿Son productos naturales?\n\nSí, utilizamos leche de calidad con ingredientes naturales. No contienen conservantes artificiales añadidos.\n\n### ¿Dónde puedo comprar productos PIL?\n\nNuestros productos están disponibles en:\n- Supermercados principales\n- Tiendas de abarrotes\n- Distribuidoras de lácteos\n- Plataformas de compra online",
                ],
                [
                    'title' => 'Información nutricional de productos PIL',
                    'excerpt' => 'Detalles nutricionales y recomendaciones de consumo',
                    'content' => "## Información Nutricional PIL\n\n### Leche Fresca PIL\n\n**Composición por 100ml:**\n- Energía: 61 kcal\n- Proteína: 3.2g\n- Grasa: 3.6g\n- Carbohidratos: 4.8g\n- Calcio: 120mg\n- Vitamina A: 40 UI\n\n### Beneficios de Consumir Lácteos\n\n1. **Fortalecimiento óseo**: Alto contenido de calcio\n2. **Desarrollo muscular**: Proteínas de calidad\n3. **Inmunidad**: Vitaminas y minerales esenciales\n4. **Salud digestiva**: Contiene lactobacilos beneficiosos\n\n### Recomendaciones de Consumo\n\n- Niños (4-8 años): 2 tazas diarias\n- Niños (9-13 años): 3 tazas diarias\n- Adultos: 3 tazas diarias\n- Embarazadas: 4 tazas diarias\n\n### Alergias e Intolerancias\n\nContiene **lactosa y proteínas de leche de vaca**. Si tienes intolerancia a la lactosa, consulta nuestros productos deslactosados.",
                ],
            ],
        ],
        [
            'name' => 'Banco Fassil S.A.',
            'legal_name' => 'Banco Fassil S.A. - Servicios Financieros',
            'support_email' => 'soporte@fassil.com.bo',
            'phone' => '+59133158000',
            'city' => 'Santa Cruz',
            'address' => 'Libertad 765, Centro',
            'state' => 'Santa Cruz',
            'postal_code' => '00000',
            'tax_id' => '151236547',
            'legal_rep' => 'Fernando Mendoza López',
            'website' => 'https://www.fassil.com.bo',
            'industry_code' => 'finance',
            'company_admin' => [
                'first_name' => 'Fernando',
                'last_name' => 'Mendoza',
                'email' => 'fernando.mendoza@fassil.com.bo',
            ],
            'agents' => [
                [
                    'first_name' => 'Laura',
                    'last_name' => 'Gutierrez',
                    'email' => 'laura.gutierrez@fassil.com.bo',
                ],
                [
                    'first_name' => 'Carlos',
                    'last_name' => 'Morales',
                    'email' => 'carlos.morales@fassil.com.bo',
                ],
            ],
            'articles' => [
                [
                    'title' => 'Cómo abrir una cuenta en Banco Fassil',
                    'excerpt' => 'Procedimiento simple para abrir tu cuenta bancaria',
                    'content' => "## Cómo Abrir una Cuenta en Banco Fassil\n\n### Requisitos\n\n1. **Documentos de Identidad**:\n   - Cédula de identidad original y fotocopia\n   - Para extranjeros: Pasaporte vigente\n\n2. **Comprobante de Domicilio**:\n   - Factura de servicios (agua, luz, gas)\n   - Recibo de alquiler\n   - No mayor a 3 meses\n\n3. **Comprobante de Ingresos**:\n   - Liquidación de sueldo\n   - Declaración de impuestos\n   - Certificado laboral\n\n### Pasos para Abrir Cuenta\n\n**Paso 1: Visita una Sucursal**\nPresenta los documentos en cualquier sucursal de Banco Fassil\n\n**Paso 2: Completa el Formulario**\nLlena la solicitud con tus datos personales\n\n**Paso 3: Verificación**\nNuestro equipo verifica tu información\n\n**Paso 4: Depósito Inicial**\nDeposita el monto mínimo requerido\n\n**Paso 5: Recibe tu Tarjeta**\nTu tarjeta de débito llega en 5-7 días hábiles\n\n### Horario de Atención\n\n- **Lunes a Viernes**: 08:30 - 17:00\n- **Sábado**: 08:30 - 12:30\n- **Domingos y Festivos**: Cerrado",
                ],
                [
                    'title' => 'Servicios de crédito disponibles',
                    'excerpt' => 'Conoce las opciones de crédito que Banco Fassil te ofrece',
                    'content' => "## Servicios de Crédito Banco Fassil\n\n### Tipos de Crédito\n\n#### 1. Crédito Personal\n- **Monto**: Hasta Bs. 50,000\n- **Plazo**: 12-60 meses\n- **Tasa**: A partir del 8% anual\n- **Uso**: Libre disponibilidad\n\n#### 2. Crédito Hipotecario\n- **Monto**: Hasta Bs. 200,000\n- **Plazo**: Hasta 25 años\n- **Tasa**: A partir del 5% anual\n- **Uso**: Compra de vivienda\n\n#### 3. Crédito Vehicular\n- **Monto**: Hasta Bs. 100,000\n- **Plazo**: Hasta 7 años\n- **Tasa**: A partir del 7% anual\n- **Uso**: Compra de vehículos\n\n#### 4. Crédito Empresarial\n- **Monto**: Desde Bs. 100,000\n- **Plazo**: A medida\n- **Tasa**: Negociable\n- **Uso**: Capital de trabajo e inversión\n\n### Proceso de Solicitud\n\n1. Completar solicitud de crédito\n2. Presentar documentación requerida\n3. Evaluación de solvencia\n4. Aprobación (3-5 días hábiles)\n5. Desembolso de fondos",
                ],
                [
                    'title' => 'Banca en línea - Guía de uso',
                    'excerpt' => 'Cómo usar los servicios de banca en línea de Fassil',
                    'content' => "## Banca en Línea Fassil\n\n### Registro\n\n1. Entra a www.fassil.com.bo\n2. Haz clic en \"Banca en Línea\"\n3. Ingresa tu número de cédula\n4. Crea tu contraseña personal\n5. Confirma tu correo electrónico\n\n### Funcionalidades Disponibles\n\n#### Consultas\n- Saldo de cuentas\n- Movimientos\n- Extractos\n- Tasa de cambio\n\n#### Transferencias\n- Transferencias entre cuentas propias\n- Transferencias a terceros (Fassil y otros bancos)\n- Pagos a proveedores\n\n#### Pago de Servicios\n- Pago de servicios básicos\n- Pago de tarjetas de crédito\n- Pago de préstamos\n\n### Seguridad\n\n✓ Contraseña segura (mínimo 8 caracteres)\n✓ Clave de seguridad SMS\n✓ Certificado digital SSL\n✓ No compartir credenciales\n✓ Cambiar contraseña cada 90 días\n\n### Soporte\n\nPara problemas técnicos:\n- **Línea de soporte**: 800-10-2500\n- **Email**: soporte.banca@fassil.com.bo\n- **Horario**: 24/7",
                ],
            ],
        ],
        [
            'name' => 'YPFB Corporación',
            'legal_name' => 'Yacimientos Petrolíferos Fiscales Bolivianos S.A.',
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
            ],
            'articles' => [
                [
                    'title' => 'Reportar derrames o accidentes ambientales',
                    'excerpt' => 'Procedimiento para reportar incidentes ambientales',
                    'content' => "## Reporte de Derrames y Accidentes Ambientales\n\n### ¿Qué es un Derrame?\n\nUna liberación no controlada de petróleo, gas natural o derivados al ambiente.\n\n### Cómo Reportar\n\n#### 1. Llamada Inmediata\n- **Línea de Emergencia**: +591-2-2106565\n- **Centro de Control**: Disponible 24/7\n- Proporciona ubicación exacta\n\n#### 2. Información Requerida\n- Ubicación del derrame (coordenadas GPS si es posible)\n- Tipo de producto derramado\n- Volumen estimado\n- Condiciones climáticas\n- Fotos o videos (si es seguro obtenerlos)\n\n#### 3. Equipo de Respuesta\nNuestro equipo:\n- Evalúa el incidente\n- Inicia contención\n- Documenta los daños\n- Informa a autoridades\n\n### Protocolo de Seguridad\n\n✓ NO acercarse directamente al derrame\n✓ Mantener distancia segura\n✓ Avisar a personas cercanas\n✓ Esperar instrucciones de personal especializado",
                ],
                [
                    'title' => 'Información sobre operaciones de gas y petróleo',
                    'excerpt' => 'Conozca las operaciones principales de YPFB',
                    'content' => "## Operaciones de Gas y Petróleo en Bolivia\n\n### Campos Productivos Principales\n\n#### 1. Campos de Gas Natural\n- **Ubicación**: Departamento de Tarija\n- **Reservas**: 10.6 TCF (Trillion Cubic Feet)\n- **Producción**: Exportación a Argentina y Brasil\n\n#### 2. Campos de Petróleo\n- **Ubicación**: Departamento de Santa Cruz\n- **Reservas**: 430 millones de barriles\n- **Producción**: Abastecimiento interno y exportación\n\n### Cadena de Producción\n\n1. **Exploración**: Búsqueda de nuevas reservas\n2. **Explotación**: Extracción de recursos\n3. **Transporte**: Oleoductos y gasoductos\n4. **Refinación**: Procesamiento en refinerías\n5. **Distribución**: A mercados internos y externos\n\n### Impacto Económico\n\n- Generación de empleos\n- Ingresos fiscales\n- Desarrollo regional\n- Infraestructura\n\n### Compromiso Ambiental\n\nYPFB se compromete a:\n- Minimizar impacto ambiental\n- Cumplir regulaciones ambientales\n- Restaurar ecosistemas\n- Capacitación en seguridad",
                ],
                [
                    'title' => 'Programas de empleo y desarrollo en YPFB',
                    'excerpt' => 'Oportunidades laborales y capacitación profesional',
                    'content' => "## Empleo y Desarrollo en YPFB\n\n### Oportunidades Laborales\n\n#### Posiciones Técnicas\n- Ingenieros de Petróleo\n- Ingenieros Civiles\n- Técnicos en Operaciones\n- Especialistas en Seguridad\n\n#### Posiciones Administrativas\n- Contadores\n- Analistas de Sistemas\n- Especialistas en Recursos Humanos\n- Abogados\n\n### Requisitos Generales\n\n✓ Título profesional\n✓ Experiencia según puesto (2-5 años)\n✓ Examen médico\n✓ Antecedentes limpios\n✓ Disponibilidad para trabajar en zonas remotas\n\n### Programa de Capacitación\n\n**YPFB ofrece:**\n- Formación técnica inicial\n- Capacitación continua\n- Programas de especialización\n- Becas para postgrados\n- Intercambios profesionales\n\n### Beneficios\n\n- Salario competitivo\n- Seguro de salud\n- Bonificaciones por desempeño\n- Oportunidades de ascenso\n- Ambiente laboral seguro",
                ],
            ],
        ],
        [
            'name' => 'Tigo Bolivia S.A.',
            'legal_name' => 'Tigo Bolivia S.A. - Telecomunicaciones',
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
            ],
            'articles' => [
                [
                    'title' => 'Cómo contratar servicios Tigo',
                    'excerpt' => 'Guía para contratar internet, celular o televisión',
                    'content' => "## Cómo Contratar Servicios Tigo\n\n### Opciones de Contratación\n\n#### 1. En Línea\n- Entra a www.tigo.com.bo\n- Selecciona tu plan\n- Completa tus datos\n- Realiza el pago\n- Activación en 24 horas\n\n#### 2. En Tienda Tigo\n- Visita la sucursal más cercana\n- Elige tu plan con un asesor\n- Completa documentos\n- Realiza el pago\n- Recibe tu SIM o equipo\n\n#### 3. Por Teléfono\n- Llama al +591-800-17-5000\n- Indica el servicio deseado\n- El asesor gestiona tu contrato\n- Pago por transferencia bancaria\n\n### Planes Disponibles\n\n#### Internet Fijo\n- 10 Mbps - Bs. 99\n- 30 Mbps - Bs. 149\n- 50 Mbps - Bs. 199\n- 100 Mbps - Bs. 299\n\n#### Planes Celulares\n- Prepago: Recarga desde Bs. 10\n- Postpago: Desde Bs. 99 mensuales\n- Planes ejecutivos: A medida\n\n#### Televisión\n- Plan Básico: 80 canales\n- Plan Plus: 150 canales\n- Plan Premium: 200+ canales + películas\n\n### Documentos Requeridos\n\n- Cédula de identidad\n- Comprobante de domicilio\n- Comprobante de ingresos (para postpago)",
                ],
                [
                    'title' => 'Solución de problemas de conexión',
                    'excerpt' => 'Pasos para resolver problemas comunes de internet',
                    'content' => "## Solución de Problemas de Conexión\n\n### Problema: Internet Lento\n\n**Paso 1: Reinicia el módem**\n- Desconecta el cable de poder\n- Espera 30 segundos\n- Vuelve a conectar\n- Espera 2 minutos\n\n**Paso 2: Verifica la distancia**\n- Coloca el módem en posición central\n- Evita obstáculos grandes\n- Mantén alejado de otros aparatos electrónicos\n\n**Paso 3: Revisa el número de dispositivos**\n- Desconecta dispositivos que no uses\n- Cierra aplicaciones que consumen datos\n- Usa ethernet para mayor velocidad\n\n### Problema: Sin Conexión\n\n**Paso 1: Verifica las luces del módem**\n- Luz roja = Sin señal de Tigo\n- Luz amarilla = Conectando\n- Luz verde = Conectado\n\n**Paso 2: Comprueba el cable**\n- Verifica conexiones físicas\n- Reemplaza cable si está dañado\n- Prueba en otro puerto\n\n**Paso 3: Reinicia el módem**\n(Ver pasos anteriores)\n\n### Problema: Conexión Inestable\n\n**Paso 1: Actualiza el firmware**\n- Accede a 192.168.1.1\n- Busca actualización disponible\n- Descarga e instala\n\n**Paso 2: Cambia la frecuencia WiFi**\n- En configuración del módem\n- Intenta frecuencia 2.4GHz o 5GHz\n\n**Paso 3: Contacta a soporte**\n- Si persiste el problema\n- Llama al +591-800-17-5000",
                ],
                [
                    'title' => 'Información sobre tarifas y promociones',
                    'excerpt' => 'Conozca nuestras promociones actuales y beneficios',
                    'content' => "## Tarifas y Promociones Tigo\n\n### Promociones Vigentes\n\n#### Promoción Internet + Celular\n- **50 Mbps + Plan Celular 5GB**\n- Bs. 249 mensuales\n- 2 primeros meses: Bs. 149\n- Vigencia: Hasta 31 de diciembre\n\n#### Promoción Triple Play\n- **Internet 30Mbps + TV + Celular**\n- Bs. 349 mensuales\n- Instalación gratuita\n- Router WiFi 6 incluido\n\n#### Descuentos por Fidelidad\n- 1 año: 5% descuento\n- 2 años: 10% descuento\n- 3+ años: 15% descuento\n\n### Beneficios Adicionales\n\n✓ Instalación y configuración gratis\n✓ Modem inalámbrico incluido\n✓ Llamadas internacionales incluidas\n✓ Protección contra virus\n✓ Soporte técnico 24/7\n✓ Antivirus y firewall incluidos\n\n### Programa de Referidos\n\n- Refiere a un amigo: **Bs. 50**\n- Tu amigo obtiene: **Primer mes 50% descuento**\n- Sin límite de referidos\n\n### Garantía de Servicio\n\n- Disponibilidad: 99.9%\n- Tiempo de respuesta: Máximo 24 horas\n- Reemplazo de equipos: Garantía 12 meses\n- Servicio de respaldo: Datos ilimitados para emergencias",
                ],
            ],
        ],
        [
            'name' => 'Cervecería Boliviana Nacional S.A.',
            'legal_name' => 'Cervecería Boliviana Nacional S.A. - Bebidas',
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
            ],
            'articles' => [
                [
                    'title' => 'Historia y tradición de CBN',
                    'excerpt' => 'Conozca la historia de Cervecería Boliviana Nacional',
                    'content' => "## Historia de Cervecería Boliviana Nacional\n\n### Orígenes\n\nCervecería Boliviana Nacional fue fundada en **1877** en La Paz, convirtiéndose en la empresa cervecera más antigua de Bolivia.\n\n### Hitos Importantes\n\n**1877** - Fundación en La Paz\n**1950** - Creación de la marca **Paceña**\n**1980** - Introducción de **Huari** en el mercado\n**2000** - Expansión a Cochabamba\n**2015** - Innovación en línea de cervezas artesanales\n\n### Marcas Principales\n\n#### Paceña\n- Cerveza clásica de pilsner\n- 4.8% alcohol\n- Producida desde 1950\n- Icono de identidad boliviana\n\n#### Huari\n- Cerveza oscura (Munich Dunkel)\n- 5.2% alcohol\n- Sabor robusto y maltoso\n- Preferida en épocas de frío\n\n#### Nuevas Líneas\n- Cervezas artesanales\n- Cerveza sin alcohol\n- Cervezas con sabores regionales\n\n### Compromiso Actual\n\n✓ Calidad premium\n✓ Ingredientes seleccionados\n✓ Proceso tradicional\n✓ Innovación constante\n✓ Sostenibilidad ambiental\n\n### Presencia en Bolivia\n\n- Plantas en: La Paz, Cochabamba, Santa Cruz\n- Distribución nacional\n- Exportación a países vecinos",
                ],
                [
                    'title' => 'Información nutricional de bebidas CBN',
                    'excerpt' => 'Detalles nutricionales de nuestras bebidas',
                    'content' => "## Información Nutricional CBN\n\n### Cerveza Paceña (350ml)\n\n**Composición:**\n- Energía: 148 kcal\n- Carbohidratos: 10g\n- Proteína: 1.5g\n- Sodio: 20mg\n- % Alcohol: 4.8%\n\n### Cerveza Huari (350ml)\n\n**Composición:**\n- Energía: 162 kcal\n- Carbohidratos: 12g\n- Proteína: 1.8g\n- Sodio: 25mg\n- % Alcohol: 5.2%\n\n### Cerveza Sin Alcohol (350ml)\n\n**Composición:**\n- Energía: 98 kcal\n- Carbohidratos: 8g\n- Proteína: 0.5g\n- Sodio: 15mg\n- % Alcohol: 0.0%\n\n### Ingredientes Principales\n\n- **Malta de cebada**: Base principal\n- **Lúpulos**: Para sabor y aroma\n- **Levadura**: Para fermentación\n- **Agua**: Destilada y filtrada\n- **Conservantes naturales**: Dióxido de carbono\n\n### Información Importante\n\n⚠️ Contiene gluten (derivado de cebada)\n⚠️ No recomendado durante embarazo\n⚠️ Consumo responsable recomendado\n✓ Apta para mayores de 18 años\n✓ Almacenar en lugar fresco\n✓ Consumir antes de la fecha de vencimiento",
                ],
                [
                    'title' => 'Responsabilidad social y sustentabilidad',
                    'excerpt' => 'Nuestro compromiso con el ambiente y la comunidad',
                    'content' => "## Responsabilidad Social CBN\n\n### Compromiso Ambiental\n\n#### Reducción de Residuos\n- Botellas 100% reciclables\n- Programa de devolución de envases\n- Reducción de plástico en empaques\n- Tratamiento de aguas residuales\n\n#### Energías Limpias\n- Instalación de paneles solares\n- Reducción de consumo energético\n- Eficiencia en procesos productivos\n- Auditorías ambientales periódicas\n\n#### Gestión del Agua\n- Sistema de recirculación\n- Tratamiento antes de descargar\n- Conservación de recursos hídricos\n- Monitoreo de calidad\n\n### Responsabilidad Social\n\n#### Empleabilidad\n- Generación de 2,000+ empleos directos\n- Capacitación continua\n- Condiciones laborales seguras\n- Oportunidades de crecimiento\n\n#### Comunidad\n- Apoyo a emprendimientos locales\n- Programas de educación\n- Patrocinio de eventos culturales\n- Donaciones a organizaciones sociales\n\n#### Consumo Responsable\n- Campañas contra conducción bajo efecto\n- Promoción de consumo moderado\n- Información sobre productos\n- Apoyo a programas de salud\n\n### Certificaciones\n\n✓ ISO 9001 (Calidad)\n✓ ISO 14001 (Ambiental)\n✓ OHSAS 18001 (Seguridad)\n✓ Estándares internacionales de calidad",
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🏢 Creando 5 empresas bolivianas reales con datos profesionales...');

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
                $companyCode = CodeGenerator::generate('business.companies', CodeGenerator::COMPANY, 'company_code');

                $companyService = app(CompanyService::class);
                $company = $companyService->create([
                    'company_code' => $companyCode,
                    'name' => $companyData['name'],
                    'legal_name' => $companyData['legal_name'],
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

                // 6. Crear 3 artículos de Help Center
                $this->createHelpCenterArticles($company, $companyData['articles']);

            } catch (\Exception $e) {
                $this->command->error("❌ Error creando empresa: {$e->getMessage()}");
            }
        }

        $this->command->info('✅ Seeder completado con éxito!');
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

    private function createHelpCenterArticles(Company $company, array $articles): void
    {
        $category = ArticleCategory::first();

        if (!$category) {
            $this->command->warn("⚠ No hay categorías de Help Center disponibles");
            return;
        }

        // El admin_user_id está directamente en la empresa
        $authorId = $company->admin_user_id;

        foreach ($articles as $articleData) {
            try {
                $article = HelpCenterArticle::create([
                    'company_id' => $company->id,
                    'category_id' => $category->id,
                    'author_id' => $authorId,
                    'title' => $articleData['title'],
                    'excerpt' => $articleData['excerpt'],
                    'content' => $articleData['content'],
                    'status' => 'PUBLISHED',
                    'views_count' => rand(0, 100),
                    'published_at' => now(),
                ]);

                $this->command->info("    ├─ Artículo creado: {$article->title}");
            } catch (\Exception $e) {
                $this->command->warn("    ├─ Error creando artículo: {$e->getMessage()}");
            }
        }
    }
}
