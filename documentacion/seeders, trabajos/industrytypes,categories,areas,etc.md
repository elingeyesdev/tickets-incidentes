# Documentación: Sistema Helpdesk

> **Documentación Técnica del Sistema Helpdesk**
> Este documento describe la estructura completa del sistema de helpdesk, incluyendo los tipos de industria soportados y las categorías de tickets disponibles para cada una.

---

## Tipos de Industria (Industry Types)

A continuación se listan todos los tipos de industria disponibles en el sistema. Cada empresa se asocia a uno de estos tipos para una mejor categorización y análisis.

### 1. Tecnología (`technology`)
Desarrollo de software, IT, SaaS

### 2. Salud (`healthcare`)
Hospitales, clínicas, servicios médicos

### 3. Educación (`education`)
Escuelas, universidades, capacitación

### 4. Finanzas (`finance`)
Seguros, inversiones

### 5. Comercio (`retail`)
Tiendas, e-commerce, minoristas

### 6. Manufactura (`manufacturing`)
Producción, fabricación industrial

### 7. Bienes Raíces (`real_estate`)
Inmobiliarias, arrendamiento

### 8. Hospitalidad (`hospitality`)
Hoteles, restaurantes, turismo

### 9. Transporte (`transportation`)
Logística, delivery, movilidad

### 10. Servicios Profesionales (`professional_services`)
Consultoría, legal, contabilidad

### 11. Medios (`media`)
Publicidad, marketing, comunicaciones

### 12. Energía (`energy`)
Electricidad, petróleo, renovables

### 13. Telecomunicaciones (`telecommunications`)
Operadores de telefonía móvil/fija, ISPs y servicios de telecom

### 14. Alimentos y Bebidas (`food_and_beverage`)
Productores, procesadores y distribuidores de alimentos y bebidas

### 15. Farmacéutica / Farmacias (`pharmacy`)
Cadenas de farmacias, distribución farmacéutica y productos de salud

### 16. Electrónica y Hardware (`electronics`)
Tiendas y distribuidores de equipos electrónicos, componentes y hardware

### 17. Banca (`banking`)
Bancos comerciales y servicios bancarios

### 18. Supermercado (`supermarket`)
Cadenas de supermercados y tiendas de abarrotes

### 19. Veterinaria (`veterinary`)
Clínicas veterinarias, servicios de cuidado animal y tiendas para mascotas

### 20. Seguros (`insurance`)
Seguros de vida, seguros comerciales, seguros de salud y pólizas especializadas

### 21. Agricultura (`agriculture`)
Cultivos, ganadería, agroindustria

### 22. Gobierno (`government`)
Entidades públicas, municipios

### 23. ONGs (`non_profit`)
Organizaciones sin fines de lucro

### 24. Construcción (`construction`)
Empresas constructoras, obras civiles, proyectos inmobiliarios

### 25. Medio Ambiente (`environment`)
Consultorías ambientales, reciclaje, energías renovables, ONGs ambientales

### 26. Otros (`other`)
Industrias no clasificadas

---

## Categorías de Tickets por Defecto (Por Industria)

Estas son las categorías de tickets que se crean automáticamente para cada empresa según su tipo de industria (`industry_code`). Cada categoría está diseñada para capturar los tipos específicos de solicitudes y problemas que requiere esa industria.

> **NOTA IMPORTANTE:** Las categorías predefinidas son parametrizables. Cada empresa puede personalizar, renombrar o agregar categorías adicionales según sus necesidades específicas. Las 4-5 categorías predefinidas sirven como punto de partida profesional. **Importante:** Las categorías responden a "¿QUÉ tipo de trabajo/incidente/solicitud reporta el usuario?" NO a "¿QUIÉN lo resuelve?" (eso son las áreas).

### 1. Tecnología (`technology`)
Desarrollo de software, IT, SaaS

**Categorías predefinidas:**

- **Reporte de Error/Bug:** Algo funciona incorrectamente, comportamiento inesperado, crash, excepción
- **Solicitud de Funcionalidad:** Solicitud de nuevas features, mejoras, cambios
- **Problema de Rendimiento:** Aplicación lenta, timeouts, recursos agotados
- **Consulta Técnica:** Preguntas sobre uso, documentación, integración
- **Problema de Acceso/Autenticación:** No puede loguear, credenciales incorrectas, permisos

### 2. Salud (`healthcare`)
Hospitales, clínicas, servicios médicos

**Categorías predefinidas:**
- **Problema con Cita:** No puedo agendar, necesito cambiar hora, quiero cancelar
- **Problema de Acceso al Sistema:** No puedo entrar a sistema EMR, credenciales no funcionan
- **Solicitud de Historial Médico:** Necesito acceso a mi historial, imprimir resultado
- **Consulta sobre Cobertura:** ¿Qué cubre el seguro? ¿Qué medicinas están cubiertas?
- **Problema de Facturación:** Cobro incorrecto, duplicado, en deuda

### 3. Educación (`education`)
Escuelas, universidades, capacitación

**Categorías predefinidas:**
- **Problema con Acceso al Curso:** No puedo entrar a clase virtual, no veo materiales
- **Consulta sobre Calificaciones:** ¿Cuál es mi nota final? ¿Cuándo salen notas?
- **Solicitud de Documento Académico:** Necesito certificado de notas, transcripción
- **Problema Técnico de Plataforma:** Plataforma lenta, video no carga, error al enviar tarea
- **Queja sobre Servicio Educativo:** Maestro desapareció, clase desorganizada, contenido malo

### 4. Finanzas (`finance`)
Seguros, inversiones, fondos

**Categorías predefinidas:**

- **Problema de Cuenta/Inversión:** Saldo no coincide, desaparición de fondos
- **Solicitud sobre Póliza/Producto:** Quiero cambiar de plan, información sobre producto nuevo
- **Problema de Transacción:** Transferencia no llegó, retiro rechazado, demora
- **Reporte de Seguridad/Fraude:** Veo movimientos sospechosos, creo que me robaron
- **Consulta sobre Políticas:** ¿Cuál es la tasa de interés? ¿Qué comisiones cobran?

### 5. Comercio (`retail`)
Tiendas, e-commerce, minoristas

**Categorías predefinidas:**

- **Problema con Pedido:** Pedido no llegó, llegó incompleto, llegó dañado
- **Problema de Pago:** Transacción rechazada, cobrado dos veces, dinero aún no refundado
- **Solicitud de Devolución/Cambio:** Quiero devolver, cambiar por otro tamaño, no me gusta
- **Consulta sobre Envío:** ¿Dónde está mi pedido? ¿Cuándo llega? ¿Costo del envío?
- **Queja sobre Calidad del Producto:** Producto de mala calidad, no como se describe

### 6. Manufactura (`manufacturing`)
Producción, fabricación industrial

**Categorías predefinidas:**

- **Problema de Equipo/Máquina:** Máquina se dañó, línea paró, mantenimiento urgente
- **Problema de Calidad del Producto:** Producto fuera de tolerancia, defecto en lote, tasa de rechazo alta
- **Problema de Cadena de Suministro:** Proveedor retrasado, materia prima defectuosa, falta stock
- **Consulta sobre Proceso/Especificación:** Cómo funciona este proceso? Cuáles son las tolerancias?
- **Solicitud de Cambio de Proceso:** Necesito cambiar temperatura, velocidad de línea, parámetros

### 7. Bienes Raíces (`real_estate`)
Inmobiliarias, arrendamiento

**Categorías predefinidas:**
- **Solicitud de Información de Propiedad:** Quiero saber propiedades disponibles, área, características
- **Problema con Arrendamiento/Contrato:** Disputa sobre cláusulas, renovación, términos no claros
- **Solicitud de Mantenimiento:** Reparación de baño, gotera, falla de electricidad, daño
- **Problema de Facturación/Pago de Renta:** Renta cobrada incorrectamente, pago no procesó, aumento sin aviso
- **Consulta sobre Disponibilidad/Términos:** Cuál es el precio? Hay depósito de garantía? Cuánto es el contrato?

### 8. Hospitalidad (`hospitality`)
Hoteles, restaurantes, turismo

**Categorías predefinidas:**
- **Problema con Reservación:** No puedo hacer reserva online, error en booking, sistema no funciona
- **Queja sobre Habitación/Servicio:** Habitación sucia, hay insectos, ruido de huéspedes, servicio lento
- **Problema de Facturación/Cobro:** Cobro incorrecto, cargo no autorizado, problema con reembolso
- **Solicitud de Servicio Especial:** Quiero room service, late checkout, extra towels, rollaway bed
- **Problema de Equipo/Infraestructura:** Baño no funciona, A/C roto, wifi no funciona, agua fría

### 9. Transporte (`transportation`)
Logística, delivery, movilidad

**Categorías predefinidas:**
- **Problema de Envío/Entrega:** Envío no llegó, llegó dañado, se perdió, retraso significativo
- **Problema de Vehículo:** Camión descompuesto, falla mecánica, necesita mantenimiento urgente
- **Consulta de Rastreo:** ¿Dónde está mi paquete? ¿Cuándo llega? Estado del envío
- **Reporte de Conductor:** Conductor fue grosero, conducción riesgosa, comportamiento inapropiado
- **Solicitud de Logística:** Cambiar fecha, agregar parada, cambiar dirección de entrega

### 10. Servicios Profesionales (`professional_services`)
Consultoría, legal, contabilidad

**Categorías predefinidas:**
- **Problema con Proyecto:** Retraso, scope creep, comunicación falla
- **Solicitud de Documentación/Reporte:** Necesito reporte del proyecto
- **Problema de Facturación/Cobro:** Factura incorrecta, disputa de costos
- **Consulta sobre Progreso/Status:** ¿Cómo va el proyecto?
- **Queja sobre Servicio:** No estoy satisfecho con el trabajo

### 11. Medios (`media`)
Publicidad, marketing, comunicaciones

**Categorías predefinidas:**
- **Solicitud de Contenido/Diseño:** Necesito banner, video, contenido
- **Problema con Campaña:** Campaña no se publicó, error en ejecución
- **Consulta sobre Disponibilidad/Precios:** ¿Hay disponibilidad? ¿Cuál es el precio?
- **Problema de Facturación/Servicios:** Cobro incorrecto, servicio no entregado
- **Queja sobre Calidad:** Diseño no es lo que pedí, resultado pobre

### 12. Energía (`energy`)
Electricidad, petróleo, renovables

**Categorías predefinidas:**

- **Incidente de Interrupción del Servicio:** Se cortó la luz, no hay suministro, apagón en zona
- **Problema de Equipo/Infraestructura:** Medidor dañado, instalación defectuosa, mantenimiento urgente
- **Problema de Facturación:** Consumo no coincide, cobro muy alto, error en cálculo
- **Reporte de Seguridad:** Línea suelta, transformador peligroso, peligro de electrocución
- **Consulta sobre Consumo/Tarifas:** Cómo bajo consumo? Cuál es mi tarifa? Qué plan es mejor?

### 13. Telecomunicaciones (`telecommunications`)
Operadores móvil/fija, ISPs

**Categorías predefinidas:**
- **Incidente de Red:** Internet caído, sin señal móvil, servicio completamente interrumpido
- **Degradación de Servicio:** Internet muy lento, llamadas cortadas, jitter en video
- **Solicitud de Activación/Cambio:** Activar línea móvil, instalar fibra, cambiar a plan superior
- **Problema de Facturación:** Cobro incorrecto, cargo duplicado, cargo no autorizado
- **Consulta sobre Planes/Disponibilidad:** Qué planes tienen? Hay cobertura en mi zona? Cuál es el mejor plan?

### 14. Alimentos y Bebidas (`food_and_beverage`)
Productores, procesadores, distribuidores

**Categorías predefinidas:**
- **Incidente de Producción:** Línea de producción parada, equipo dañado, mantenimiento urgente
- **Problema de Calidad del Producto:** Lote defectuoso, producto fuera de especificación, falla de sellado
- **Problema de Cadena de Frío/Logística:** Producto dañado por temperatura, retraso en entrega, pérdida
- **Incidente de Seguridad Alimentaria:** Contaminación detectada, alérgeno no declarado, retiro de producto
- **Solicitud/Consulta sobre Proceso:** Información sobre proceso, solicitud de cambio de parámetros, ¿cómo funciona X?

### 15. Farmacéutica / Farmacias (`pharmacy`)
Cadenas de farmacias, distribución farmacéutica

**Categorías predefinidas:**
- **Consulta Farmacéutica:** Puedo tomar esto con ese medicamento? Cuál es la dosis? Efectos secundarios?
- **Problema de Disponibilidad/Stock:** No tengo el medicamento, stock agotado, proveedor retrasado
- **Solicitud de Reposición/Orden:** Necesito reabastecer medicamentos, hacer pedido de productos
- **Problema de Facturación/Cobro:** Discrepancia en inventario, cobro incorrecto, margen incorrecto
- **Reporte de Cumplimiento/Regulación:** Auditoría fallida, documentación incompleta, medicamento vencido

### 16. Electrónica y Hardware (`electronics`)
Tiendas y distribuidores de equipos electrónicos

**Categorías predefinidas:**
- **Problema de Hardware:** Dispositivo no funciona, falla física
- **Solicitud de Configuración/Instalación:** Cómo configuro? Instalar drivers, inicializar disco
- **Solicitud/Problema de Garantía:** Necesito reparación, garantía vencida, duda sobre cobertura
- **Problema de Pedido/Envío:** Pedido no llegó, llegó dañado, unidad defectuosa en caja
- **Consulta sobre Producto/Especificaciones:** Cuáles son las especificaciones? Compatible con mi sistema?

### 17. Banca (`banking`)
Bancos comerciales y servicios bancarios

**Categorías predefinidas:**
- **Problema de Transacción:** Transacción falló, dinero no llegó, demora, reversal no aplicó
- **Problema de Cuenta:** Saldo incorrecto, acceso denegado, cuenta bloqueada, error de crédito
- **Reporte de Seguridad/Fraude:** Veo movimientos sospechosos, creo que me robaron, acceso no autorizado
- **Solicitud de Servicio Bancario:** Activar tarjeta nueva, cambiar PIN, solicitar cheques, cambiar límite
- **Consulta sobre Política/Producto:** Cuál es la comisión de transferencia? Tasa de interés? Cómo funciona X?

### 18. Supermercado (`supermarket`)
Cadenas de supermercados y tiendas de abarrotes

**Categorías predefinidas:**
- **Problema de Producto/Compra:** Producto dañado, precio diferente al mostrador, falta cantidad prometida
- **Problema de Cadena de Frío:** Producto perecedero dañado, congelador/refrigerador falla
- **Solicitud de Información/Disponibilidad:** ¿Tienen X producto? Dónde está? ¿En qué piso?
- **Problema de Facturación/Cobro:** Cobro incorrecto, doble cobro, descuento no aplicó
- **Queja sobre Servicio/Tienda:** Tienda sucia, atención mala, demora en caja, falta de stock

### 19. Veterinaria (`veterinary`)
Clínicas veterinarias y servicios de cuidado animal

**Categorías predefinidas:**
- **Solicitud de Cita/Consulta:** Quiero agendar cita
- **Problema con Cita:** No puedo agendar, cancelaron sin aviso
- **Urgencia/Emergencia Veterinaria:** Mascota está enferma, lesión, requiere atención urgente
- **Consulta sobre Historial/Medicamento:** Qué vacunas tiene? Qué medicamento darle? Cuándo vuelvo?
- **Solicitud de Suministros/Medicamentos:** Necesito alimento especial, medicamento de prescripción

### 20. Seguros (`insurance`)
Seguros de vida, comerciales, de salud

**Categorías predefinidas:**
- **Consulta sobre Cobertura/Póliza:** ¿Qué está cubierto? ¿Cuál es mi beneficio? ¿Límite de cobertura?
- **Solicitud/Cambio de Póliza:** Cambiar beneficiario, aumentar cobertura, cambiar de plan
- **Problema de Reclamación:** Reclamación rechazada, demora en pago, documentación falta, disputa
- **Problema de Facturación/Pago Premium:** Cobro incorrecto, prima no procesó, cargas duplicadas
- **Reporte de Cumplimiento:** Auditoría, actualizar información KYC, falta documentación

### 21. Agricultura (`agriculture`)
Cultivos, ganadería, agroindustria

**Categorías predefinidas:**
- **Problema de Equipo/Maquinaria:** Tractor dañado, cosechadora falla, bomba no funciona
- **Solicitud de Suministros/Insumos:** Semillas, fertilizantes, medicinas animales, alimento
- **Problema de Cultivo/Cosecha:** Plaga, enfermedad, sequía, inundación, baja producción
- **Problema de Ganado/Salud Animal:** Animal enfermo, epizootia, mortalidad alta, problema reproductivo
- **Consulta sobre Técnica/Proceso:** Cuándo sembrar? Qué variedad es mejor? Cómo combatir plaga?

### 22. Gobierno (`government`)
Entidades públicas, municipios

**Categorías predefinidas:**
- **Solicitud de Trámite/Servicio:** Sacar cédula, licencia, permiso
- **Solicitud de Documento/Certificado:** Certificado de no antecedentes, acta, constancia
- **Consulta sobre Requisitos/Proceso:** Qué documentos necesito? Cómo hago el trámite? Cuánto cuesta?
- **Queja/Reclamo:** Funcionario fue grosero, negaron injustamente, servicio fue pobre
- **Problema de Acceso a Sistema/Portal:** No puedo entrar al portal, error al subir documento, sistema lento

### 23. ONGs (`non_profit`)
Organizaciones sin fines de lucro

**Categorías predefinidas:**
- **Consulta sobre Programa/Servicios:** ¿Qué hacen ustedes? ¿Cómo puedo acceder?
- **Solicitud de Voluntariado:** Quiero ser voluntario, cómo me uno?
- **Solicitud de Donación:** Quiero donar, cómo hago?
- **Consulta sobre Beneficiario:** ¿Mi hijo sigue en programa? ¿Qué necesito llevar?
- **Queja/Feedback:** Servicio fue malo, no me ayudaron, retroalimentación

### 24. Construcción (`construction`)
Empresas constructoras, obras civiles

**Categorías predefinidas:**
- **Problema de Proyecto/Cronograma:** Retraso, cambio de diseño, alcance no claro, conflicto
- **Problema de Contratista/Proveedor:** Contratista incumple trabajo, proveedor retrasado, material defectuoso
- **Solicitud de Cambio/Variación:** Cambio de diseño, agregar elemento, cambiar material
- **Incidente de Seguridad Laboral:** Accidente en obra, peligro identificado, falta de cumplimiento de normas
- **Problema de Calidad/Inspección:** Trabajo no cumple estándar, falla de construcción, acabado pobre

### 25. Medio Ambiente (`environment`)
Consultorías ambientales, reciclaje, energías renovables, ONGs ambientales

**Categorías predefinidas:**
- **Solicitud de Proyecto/Iniciativa:** Quiero iniciar proyecto de reforestación, sostenibilidad
- **Consulta sobre Cumplimiento Ambiental:** ¿Qué regulaciones aplican? ¿Cómo certificarme?
- **Solicitud de Servicio de Reciclaje:** Recolección de residuos, clasificación
- **Problema de Gestión de Residuos:** Residuos no fueron recolectados, contaminación
- **Reporte/Incidente Ambiental:** Contaminación detectada, derrame, violación ambiental

### 26. Otros (`other`)
Industrias no clasificadas

**Categorías predefinidas:**
- **Problema General:** Algo no funciona
- **Solicitud General:** Necesito que hagan algo
- **Consulta General:** Necesito información
- **Queja/Feedback:** No estoy satisfecho
- **Reporte General:** Quiero reportar algo

---

## Listado de Empresas (Datos Exactos del Código)

Este apartado contiene el registro completo de todas las empresas registradas en el sistema, organizadas por tamaño.

### Empresas Grandes

#### 1. PIL Andina S.A.

description: Productores, procesadores y distribuidores de alimentos y bebidas
industry_code: `food_and_beverage`
industry_name: Alimentos y Bebidas

**Areas:**
- **Producción Láctea:** Recepción de leche, pasteurización, procesamiento de productos lácteos
- **Líneas de Empaque:** Envasado, etiquetado, empaque y preparación para distribución
- **Control de Calidad y Laboratorio:** Pruebas microbiológicas, análisis químicos, cumplimiento de normas ISO
- **Logística y Almacenes:** Gestión de inventarios, almacenamiento en frío, distribución de productos
- **Ventas y Canales Comerciales:** Gestión de clientes mayoristas, minoristas, negociaciones comerciales
- **Recursos Humanos:** Nómina, selección, capacitación, relaciones laborales
- **Finanzas:** Contabilidad, presupuestos, análisis financiero
- **Tesorería:** Gestión de caja, cobranzas, pagos, flujo de efectivo
- **Asuntos Legales:** Contratos, litigios, responsabilidad civil, asuntos corporativos
- **Cumplimiento y Regulación:** Normativas, auditorías, compliance
- **Mantenimiento:** Mantenimiento preventivo y correctivo de equipos
- **Seguridad Industrial:** Seguridad laboral, salud ocupacional, medio ambiente

**Ticket Categories:**
- **Incidente de Producción:** Línea de producción parada, equipo dañado, mantenimiento urgente
- **Problema de Calidad del Producto:** Lote defectuoso, producto fuera de especificación, falla de sellado
- **Problema de Cadena de Frío/Logística:** Producto dañado por temperatura, retraso en entrega, pérdida
- **Incidente de Seguridad Alimentaria:** Contaminación detectada, alérgeno no declarado, retiro de producto
- **Solicitud/Consulta sobre Proceso:** Información sobre proceso, solicitud de cambio de parámetros, ¿cómo funciona X?

#### 2. YPFB Corporación

description: Electricidad, petróleo, renovables
industry_code: `energy`
industry_name: Energía

**Areas:**
- **Exploración y Evaluación:** Evaluación de reservas, identificación de prospectos, licenciamiento de bloques
- **Explotación y Operaciones de Pozo:** Perforación, completación, producción, levantamiento artificial de crudo y gas
- **Refinación y Transformación:** Procesos de destilación, conversión, fraccionamiento de hidrocarburos
- **Transporte y Logística de Hidrocarburos:** Operación de oleoductos, gaseoductos, terminales y almacenes
- **Comercialización y Ventas:** Venta de crudo, gas natural, combustibles y productos derivados
- **Seguridad, Salud y Ambiente:** Cumplimiento normativo ambiental, prevención de riesgos, gestión de contingencias
- **Ingeniería y Proyectos:** Diseño de instalaciones, ejecución de proyectos, consultoría técnica
- **Finanzas:** Contabilidad, presupuestos, análisis financiero
- **Tesorería:** Gestión de caja, cobranzas, pagos
- **Recursos Humanos:** Nómina, reclutamiento, capacitación, relaciones laborales
- **Asuntos Legales:** Contratos, litigios, asuntos corporativos
- **Cumplimiento Regulatorio:** Compliance regulatorio, auditorías, normativas

**Ticket Categories:**
- **Incidente de Interrupción del Servicio:** Se cortó la luz, no hay suministro, apagón en zona
- **Problema de Equipo/Infraestructura:** Medidor dañado, instalación defectuosa, mantenimiento urgente
- **Problema de Facturación:** Consumo no coincide, cobro muy alto, error en cálculo
- **Reporte de Seguridad:** Línea suelta, transformador peligroso, peligro de electrocución
- **Consulta sobre Consumo/Tarifas:** Cómo bajo consumo? Cuál es mi tarifa? Qué plan es mejor?

#### 3. Entel S.A.

description: Operadores de telefonía móvil/fija, ISPs y servicios de telecom
industry_code: `telecommunications`
industry_name: Telecomunicaciones

**Areas:**
- **Infraestructura de Red Móvil:** Diseño, despliegue y operación de torres, antenas, estaciones base 4G/5G
- **Infraestructura de Red Fija:** Fibra óptica, cableado, centrales telefónicas, backbone nacional
- **Operaciones de Telecomunicaciones:** Monitoreo de redes, activación de servicios, gestión de tráfico
- **Centro de Atención al Cliente:** Call center 24/7, resolución de incidentes, soporte técnico
- **Comercial y Ventas:** Adquisición de clientes, planes corporativos, retención, churn
- **Tecnología e Innovación:** Desarrollo de plataformas digitales, banca móvil, sistemas de información
- **Facturación:** Facturación de servicios, cobranzas, análisis de cargos
- **Finanzas:** Presupuestos, análisis financiero, tesorería
- **Recursos Humanos:** Nómina, reclutamiento, capacitación, relaciones laborales
- **Asuntos Legales:** Contratos, litigios, asuntos corporativos
- **Cumplimiento Regulatorio:** Compliance regulatorio, auditorías, normativas

**Ticket Categories:**
- **Incidente de Red:** Internet caído, sin señal móvil, servicio completamente interrumpido
- **Degradación de Servicio:** Internet muy lento, llamadas cortadas, jitter en video
- **Solicitud de Activación/Cambio:** Activar línea móvil, instalar fibra, cambiar a plan superior
- **Problema de Facturación:** Cobro incorrecto, cargo duplicado, cargo no autorizado
- **Consulta sobre Planes/Disponibilidad:** Qué planes tienen? Hay cobertura en mi zona? Cuál es el mejor plan?

#### 4. Tigo Bolivia S.A.

description: Operadores de telefonía móvil/fija, ISPs y servicios de telecom
industry_code: `telecommunications`
industry_name: Telecomunicaciones

**Areas:**
- **Red Móvil y Cobertura:** Infraestructura 3G/4G/LTE, torres, antenas, operación de centrales móviles
- **Servicios de Datos e Internet:** Planes de datos, internet móvil, redes privadas virtuales (VPN)
- **Operaciones de Red y Monitoreo:** Monitoreo de tráfico, activación de servicios, gestión de incidentes
- **Centro de Atención al Cliente:** Call center 24/7, resolución de problemas, soporte técnico
- **Comercial y Gestión de Suscriptores:** Adquisición de clientes, planes corporativos, retención, churn
- **Sistemas e Innovación Digital:** Aplicaciones móviles, plataformas digitales, transformación tecnológica
- **Facturación:** Facturación automática, análisis de cargos
- **Tesorería:** Cobranzas, flujo de efectivo, pagos
- **Finanzas:** Presupuestos, análisis financiero
- **Recursos Humanos:** Nómina, reclutamiento, capacitación, relaciones laborales
- **Asuntos Legales:** Contratos, litigios, asuntos corporativos
- **Cumplimiento Regulatorio:** Compliance regulatorio, auditorías normativas

**Ticket Categories:**
- **Incidente de Red:** Internet caído, sin señal móvil, servicio completamente interrumpido
- **Degradación de Servicio:** Internet muy lento, llamadas cortadas, jitter en video
- **Solicitud de Activación/Cambio:** Activar línea móvil, instalar fibra, cambiar a plan superior
- **Problema de Facturación:** Cobro incorrecto, cargo duplicado, cargo no autorizado
- **Consulta sobre Planes/Disponibilidad:** Qué planes tienen? Hay cobertura en mi zona? Cuál es el mejor plan?

#### 5. Cervecería Boliviana Nacional S.A.

description: Productores y distribuidores de alimentos y bebidas
industry_code: `food_and_beverage`
industry_name: Alimentos y Bebidas

**Areas:**
- **Producción de Cerveza:** Malteado, fermentación, maduración, procesamiento de bebidas
- **Envasado y Embotellado:** Llenado, etiquetado, encajonado y preparación para distribución
- **Control de Calidad:** Pruebas de sabor, carbonatación, microbiología, cumplimiento de normas
- **Laboratorio:** Análisis químicos, pruebas técnicas, certificaciones
- **Logística y Cadena de Frío:** Almacenamiento en frío, distribución nacional, gestión de inventarios
- **Ventas y Comercial:** Gestión de clientes mayoristas, minoristas, negociaciones comerciales
- **Recursos Humanos:** Nómina, selección, capacitación, relaciones laborales
- **Finanzas:** Contabilidad, presupuestos, análisis financiero
- **Tesorería:** Gestión de caja, cobranzas, pagos
- **Asuntos Legales:** Contratos, litigios, asuntos corporativos
- **Cumplimiento y Regulación:** Normativas sanitarias, auditorías, compliance
- **Mantenimiento:** Mantenimiento preventivo y correctivo de equipos
- **Seguridad Industrial:** Seguridad laboral, salud ocupacional, medio ambiente

**Ticket Categories:**
- **Incidente de Producción:** Línea de producción parada, equipo dañado, mantenimiento urgente
- **Problema de Calidad del Producto:** Lote defectuoso, producto fuera de especificación, falla de sellado
- **Problema de Cadena de Frío/Logística:** Producto dañado por temperatura, retraso en entrega, pérdida
- **Incidente de Seguridad Alimentaria:** Contaminación detectada, alérgeno no declarado, retiro de producto
- **Solicitud/Consulta sobre Proceso:** Información sobre proceso, solicitud de cambio de parámetros, ¿cómo funciona X?

#### 6. Banco Mercantil Santa Cruz S.A.

description: Bancos comerciales y servicios bancarios
industry_code: `banking`
industry_name: Banca

**Areas:**
- **Banca Corporativa:** Créditos empresariales, financiamiento de proyectos, productos especializados
- **Banca de Personas:** Cuentas corrientes, depósitos, préstamos personales, tarjetas de crédito
- **Operaciones Bancarias:** Procesamiento de transacciones, cambio de moneda, clearing interbancario
- **Gestión de Riesgos:** Riesgo crediticio, riesgo operacional, análisis de exposición
- **Cumplimiento y Regulación:** Cumplimiento normativo, AML/CFT, auditorías, KYC
- **Tecnología e Innovación:** Core bancario, banca digital, ciberseguridad, canales electrónicos
- **Administración General:** Administración corporativa, procesos administrativos, gestión operativa
- **Recursos Humanos:** Nómina, reclutamiento, capacitación, relaciones laborales
- **Contabilidad:** Contabilidad general, registros contables, reportes financieros
- **Finanzas:** Presupuestos, análisis financiero, flujo de caja
- **Tesorería:** Gestión de caja, inversiones, pagos
- **Asuntos Legales:** Contratos, litigios, responsabilidad civil, asuntos corporativos

**Ticket Categories:**
- **Problema de Transacción:** Transacción falló, dinero no llegó, demora, reversal no aplicó
- **Problema de Cuenta:** Saldo incorrecto, acceso denegado, cuenta bloqueada, error de crédito
- **Reporte de Seguridad/Fraude:** Veo movimientos sospechosos, creo que me robaron, acceso no autorizado
- **Solicitud de Servicio Bancario:** Activar tarjeta nueva, cambiar PIN, solicitar cheques, cambiar límite
- **Consulta sobre Política/Producto:** Cuál es la comisión de transferencia? Tasa de interés? Cómo funciona X?

### Empresas Medianas

#### 1. Banco Fassil S.A.

description: Bancos comerciales y servicios bancarios
industry_code: `banking`
industry_name: Banca

**Areas:**
- **Operaciones Bancarias:** Procesamiento de transacciones, clearing interbancario
- **Créditos y Colocaciones:** Créditos empresariales, créditos personales, análisis crediticio
- **Atención al Cliente:** Servicio al cliente, resolución de consultas, gestión de reclamos
- **Gestión de Riesgos:** Riesgo crediticio, riesgo operacional, análisis de exposición, AML/CFT
- **Cumplimiento Regulatorio:** Compliance normativo, auditorías internas, normativas, KYC
- **Tecnología:** Sistemas bancarios, seguridad digital, ciberseguridad, infraestructura TI
- **Finanzas:** Presupuestos, análisis financiero
- **Contabilidad:** Registros contables, reportes financieros
- **Tesorería:** Gestión de caja, cobranzas, pagos
- **Recursos Humanos:** Nómina, contratación, capacitación
- **Asuntos Legales:** Contratos, litigios, responsabilidad civil, asuntos corporativos

**Ticket Categories:**
- **Problema de Transacción:** Transacción falló, dinero no llegó, demora, reversal no aplicó
- **Problema de Cuenta:** Saldo incorrecto, acceso denegado, cuenta bloqueada, error de crédito
- **Reporte de Seguridad/Fraude:** Veo movimientos sospechosos, creo que me robaron, acceso no autorizado
- **Solicitud de Servicio Bancario:** Activar tarjeta nueva, cambiar PIN, solicitar cheques, cambiar límite
- **Consulta sobre Política/Producto:** Cuál es la comisión de transferencia? Tasa de interés? Cómo funciona X?

#### 2. Hipermaxi S.A.

description: Cadenas de supermercados y tiendas de abarrotes
industry_code: `supermarket`
industry_name: Supermercado

**Areas:**
- **Operaciones de Tiendas:** Gestión de supermercados y sucursales, atención en tienda, horarios operativos
- **Gestión de Inventarios:** Reposición, control de stock, inventario físico, rotación de productos
- **Control de Calidad:** Inspección de productos, estándares de calidad
- **Perecibles y Cadena de Frío:** Manejo de perecederos, almacenamiento en frío, temperatura controlada
- **Logística y Distribución:** Cadena de suministro, almacenes centrales, transporte de productos
- **Ventas y Comercial:** Negociaciones comerciales, canales de venta
- **Promociones y Precios:** Ofertas, estrategia de precios, promociones
- **Atención al Cliente:** Servicio en cajas, devoluciones, quejas, satisfacción del cliente
- **Recursos Humanos:** Nómina, contratación, capacitación de personal
- **Finanzas:** Presupuestos, análisis financiero
- **Contabilidad:** Registros contables, reportes financieros
- **Sistemas e IT:** Sistemas de puntos de venta, inventarios, infraestructura TI

**Ticket Categories:**
- **Problema de Producto/Compra:** Producto dañado, precio diferente al mostrador, falta cantidad prometida
- **Problema de Cadena de Frío:** Producto perecedero dañado, congelador/refrigerador falla
- **Solicitud de Información/Disponibilidad:** ¿Tienen X producto? Dónde está? ¿En qué piso?
- **Problema de Facturación/Cobro:** Cobro incorrecto, doble cobro, descuento no aplicó
- **Queja sobre Servicio/Tienda:** Tienda sucia, atención mala, demora en caja, falta de stock

#### 3. Sofía Ltda.

description: Productores, procesadores y distribuidores de alimentos y bebidas
industry_code: `food_and_beverage`
industry_name: Alimentos y Bebidas

**Areas:**
- **Producción Avícola:** Incubación, crianza, engorde, faenado de aves
- **Procesamiento de Alimentos:** Fabricación de pastas, harinas, galletas, chocolates, líneas de producción
- **Control de Calidad:** ISO 9001, ISO 22000, buenas prácticas de manufactura, análisis de productos
- **Logística:** Cadena de frío, almacenamiento
- **Distribución:** Distribución nacional, gestión de inventarios
- **Recursos Humanos:** Nómina, contratación, capacitación, relaciones laborales
- **Seguridad y Salud Ocupacional:** Seguridad laboral, salud ocupacional, protección ambiental
- **Finanzas:** Presupuestos, análisis financiero
- **Contabilidad:** Contabilidad general, registros contables, reportes financieros
- **Tesorería:** Gestión de caja, cobranzas, pagos
- **Asuntos Legales:** Contratos, litigios, asuntos corporativos
- **Cumplimiento Normativo:** Cumplimiento normativo sanitario, regulaciones avícolas y alimentarias
- **Sistemas e IT:** Infraestructura TI, sistemas administrativos, automatización, ciberseguridad

**Ticket Categories:**
- **Incidente de Producción:** Línea de producción parada, equipo dañado, mantenimiento urgente
- **Problema de Calidad del Producto:** Lote defectuoso, producto fuera de especificación, falla de sellado
- **Problema de Cadena de Frío/Logística:** Producto dañado por temperatura, retraso en entrega, pérdida
- **Incidente de Seguridad Alimentaria:** Contaminación detectada, alérgeno no declarado, retiro de producto
- **Solicitud/Consulta sobre Proceso:** Información sobre proceso, solicitud de cambio de parámetros, ¿cómo funciona X?

#### 4. Farmacorp S.A.

description: Cadenas de farmacias, distribución farmacéutica y productos de salud
industry_code: `pharmacy`
industry_name: Farmacéutica / Farmacias

**Areas:**
- **Operaciones de Farmacias:** Gestión de 176 sucursales, atención farmacéutica, dispensación de medicamentos
- **Control de Calidad:** Inspecciones de calidad, estándares de productos
- **Buenas Prácticas de Almacenamiento (BPA):** Certificación Agemed, control de temperatura, almacenamiento seguro
- **Farmacovigilancia:** Farmacovigilancia, reportes de seguridad de medicamentos
- **Cumplimiento Regulatorio:** Cumplimiento normativas sanitarias, regulaciones farmacéuticas, auditorías
- **Logística:** Cadena de suministro farmacéutico, almacenes centrales
- **Distribución:** Transporte seguro de medicamentos, gestión de inventarios
- **Atención al Cliente:** Atención farmacéutica, resolución de consultas, gestión de reclamos
- **Servicio al Cliente:** Consultas farmacéuticas, información de medicamentos
- **Recursos Humanos:** Nómina, contratación, capacitación de farmacéuticos y personal
- **Finanzas:** Presupuestos, análisis financiero
- **Contabilidad:** Contabilidad general, registros contables, reportes financieros
- **Tesorería:** Gestión de caja, cobranzas, pagos
- **Sistemas e IT:** Sistemas de gestión farmacéutica, infraestructura TI, inventarios automatizados, ciberseguridad

**Ticket Categories:**
- **Consulta Farmacéutica:** Puedo tomar esto con ese medicamento? Cuál es la dosis? Efectos secundarios?
- **Problema de Disponibilidad/Stock:** No tengo el medicamento, stock agotado, proveedor retrasado
- **Solicitud de Reposición/Orden:** Necesito reabastecer medicamentos, hacer pedido de productos
- **Problema de Facturación/Cobro:** Discrepancia en inventario, cobro incorrecto, margen incorrecto
- **Reporte de Cumplimiento/Regulación:** Auditoría fallida, documentación incompleta, medicamento vencido

### Empresas Pequeñas

#### 1. Victoria Veterinaria

description: Clínicas veterinarias, servicios de cuidado animal y tiendas para mascotas
industry_code: `veterinary`
industry_name: Veterinaria

**Ticket Categories:**
- **Solicitud de Cita/Consulta:** Quiero agendar cita
- **Problema con Cita:** No puedo agendar, cancelaron sin aviso
- **Urgencia/Emergencia Veterinaria:** Mascota está enferma, lesión, requiere atención urgente
- **Consulta sobre Historial/Medicamento:** Qué vacunas tiene? Qué medicamento darle? Cuándo vuelvo?
- **Solicitud de Suministros/Medicamentos:** Necesito alimento especial, medicamento de prescripción

#### 2. Iris Computer

description: Tiendas y distribuidores de equipos electrónicos, componentes y hardware
industry_code: `electronics`
industry_name: Electrónica y Hardware

**Ticket Categories:**
- **Problema de Hardware:** Dispositivo no funciona, falla física
- **Solicitud de Configuración/Instalación:** Cómo configuro? Instalar drivers, inicializar disco
- **Solicitud/Problema de Garantía:** Necesito reparación, garantía vencida, duda sobre cobertura
- **Problema de Pedido/Envío:** Pedido no llegó, llegó dañado, unidad defectuosa en caja
- **Consulta sobre Producto/Especificaciones:** Cuáles son las especificaciones? Compatible con mi sistema?

#### 3. BLEETZER

description: Tiendas, e-commerce, minoristas
industry_code: `retail`
industry_name: Comercio

**Ticket Categories:**
- **Problema con Pedido:** Pedido no llegó, llegó incompleto, llegó dañado
- **Problema de Pago:** Transacción rechazada, cobrado dos veces, dinero aún no refundado
- **Solicitud de Devolución/Cambio:** Quiero devolver, cambiar por otro tamaño, no me gusta
- **Consulta sobre Envío:** ¿Dónde está mi pedido? ¿Cuándo llega? ¿Costo del envío?
- **Queja sobre Calidad del Producto:** Producto de mala calidad, no como se describe

#### 4. ISI Vapes

description: Tiendas, e-commerce, minoristas
industry_code: `retail`
industry_name: Comercio

**Ticket Categories:**
- **Problema con Pedido:** Pedido no llegó, llegó incompleto, llegó dañado
- **Problema de Pago:** Transacción rechazada, cobrado dos veces, dinero aún no refundado
- **Solicitud de Devolución/Cambio:** Quiero devolver, cambiar por otro tamaño, no me gusta
- **Consulta sobre Envío:** ¿Dónde está mi pedido? ¿Cuándo llega? ¿Costo del envío?
- **Queja sobre Calidad del Producto:** Producto de mala calidad, no como se describe

#### 5. 3B Markets

description: Cadenas de supermercados y tiendas de abarrotes
industry_code: `supermarket`
industry_name: Supermercado

**Ticket Categories:**
- **Problema de Producto/Compra:** Producto dañado, precio diferente al mostrador, falta cantidad prometida
- **Problema de Cadena de Frío:** Producto perecedero dañado, congelador/refrigerador falla
- **Solicitud de Información/Disponibilidad:** ¿Tienen X producto? Dónde está? ¿En qué piso?
- **Problema de Facturación/Cobro:** Cobro incorrecto, doble cobro, descuento no aplicó
- **Queja sobre Servicio/Tienda:** Tienda sucia, atención mala, demora en caja, falta de stock

