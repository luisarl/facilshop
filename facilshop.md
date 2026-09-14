# Documentación Técnica Completa y PRD: Fácil Shop
## Sistema Integrado de Punto de Venta (POS), Facturación Interna, Multimoneda, Financiamiento Cashea (BNPL) y Control de Inventarios

---

## SECCIÓN 1: PRODUCT REQUIREMENTS DOCUMENT (PRD)

### 1.1 Resumen Ejecutivo
**Fácil Shop** es una plataforma orientada a la gestión comercial, facturación interna en tiempo real, administración multimoneda, integración con financiamiento en cuotas **Cashea (BNPL)** y control integral de inventarios para pequeñas y medianas empresas (retail, minimarkets, boutiques, tiendas de repuestos).

El valor diferencial del sistema radica en su módulo **Punto de Venta (POS) SPA ultrarrápido**, diseñado con **Vue.js 3**, **Inertia.js** y **Pinia**, optimizado para interacción táctil y navegación completa mediante atajos de teclado (`F1`-`F12`, `ESC`) sin recargas de página. 

Soporta de forma nativa:
1. **Operación Multimoneda**: Dólares (USD), Bolívares (VES), Euros (EUR), con tasas de cambio diarias y cálculo automático de conversión en tiempo real.
2. **Plataforma Cashea (Compra Ahora, Paga Después - BNPL)**: Cálculo automático del pago inicial ("Inicial" al 40%, 50% o 60% según nivel de usuario) y distribución del saldo financiado en cuotas sin interés (típicamente 3 cuotas cada 14 días), con soporte tanto para API directa como para modo asistido/manual con código de aprobación.
3. **Cobros Multipago y Multidivisa**: Combinación de pagos (efectivo en divisas, pago móvil, tarjeta, transferencia, crédito de tienda y Cashea).
4. **Control de Inventario**: Trazabilidad inmutable mediante `movimientos_inventario`, proceso de ajustes (cabecera/detalle) y tomas físicas.
5. **Facturación Interna**: Emisión de comprobantes (Ticket, Boleta, Factura) con correlativo automático, comandos térmicos ESC/POS y PDF con código QR verificador interno.
6. **Pistas de Auditoría Integral (Audit Trail)**: Registro cronológico e inmutable de toda acción ejecutada en el sistema (quién, qué, cuándo, IP, valores anteriores y nuevos en formato JSON).

---

### 1.2 Objetivos de Negocio

| ID | Objetivo de Negocio | Indicador Clave (KPI) | Meta MVP | Alineación Técnica |
|---|---|---|---|---|
| **OBJ-01** | Reducir el tiempo de atención en caja registradora durante horas pico. | Tiempo promedio por transacción de venta. | $\le 15 \text{ segundos por venta}$ | Módulo POS con atajos de teclado, lectura de código de barras y checkout optimizado. |
| **OBJ-02** | Eliminar descuadres entre inventario físico y sistema. | Porcentaje de discrepancia en auditoría de Inventario vs Stock Real. | $< 0.5\%$ | Descuento atómico de stock en transacciones ACID con registro inmutable en movimientos de inventario. |
| **OBJ-03** | Agilizar la recuperación de ventas pausadas por atención en espera. | Tiempo de retención y reactivación de venta suspendida. | $< 3 \text{ segundos}$ | Carrito en espera con almacenamiento reactivo en Pinia / almacenamiento local. |
| **OBJ-04** | Aumentar el ticket promedio de compra mediante financiamiento Cashea. | Incremento en valor promedio del carrito mediante opción Cashea. | Incremento del $+35\%$ | Módulo de pago integrado con Cashea (Inicial + Cuotas). |
| **OBJ-05** | Aumentar ventas por crédito comercial controlado de tienda. | Tasa de adopción de compras a crédito/fiado por clientes frecuentes. | Incremento del $20\%$ | Directorio de clientes con límite de crédito y control de saldos pendientes en tiempo real. |
| **OBJ-06** | Garantizar cuadre ciego del arqueo de caja al cierre de turno. | Descuadre absoluto entre saldo teórico y efectivo contado en caja (Corte Z). | $\$0.00 \text{ de diferencia no justificada}$ | Módulo de Arqueo y Cierre Ciego de Caja multimoneda. |
| **OBJ-07** | Flexibilidad total en pagos multimoneda y tasas de cambio. | Tiempo de ajuste de tasa y cobro multimoneda sin recálculos manuales. | Conversión instantánea ($0 \text{ errores de tasa}$) | Módulo de monedas con tasa referencial (USD, VES, EUR) y pagos combinados. |
| **OBJ-08** | Minimizar pérdidas por productos vencidos o con bajo stock. | Notificaciones proactivas convertidas en reposición o liquidación. | $100\%$ de alertas atendidas a tiempo | Sistema de alertas automáticas por stock mínimo y fechas de caducidad. |
| **OBJ-09** | Trazabilidad y seguridad operativa total contra fraudes. | Cobertura de auditoría en operaciones críticas (precios, anulación, tasas). | $100\%$ de modificaciones auditadas | Módulo de pistas de auditoría con diff de valores anteriores y nuevos en JSON. |

---

### 1.3 Alcance del MVP

#### Dentro del Alcance (In-Scope)
- **Punto de Venta (POS) Multimoneda y Cola de Espera**: Búsqueda predictiva por scanner/SKU/nombre, **gestión de cola de ventas en espera (Pausar / Encolar y Reanudar múltiples tickets simultáneos)**, navegación 100% por atajos de teclado, cálculo en moneda base (ej. USD) y visualización/cobro en Bolívares (VES), Euros (EUR), etc.
- **Integración con Cashea (BNPL)**:
  - Cálculo instantáneo del desglose: Pago Inicial en tienda (40%, 50% o 60%) + Monto financiado por Cashea (3 cuotas quincenales sin interés).
  - Cobro de la "Inicial" mediante cualquiera de los métodos de tienda (Efectivo USD, Pago Móvil VES a tasa del día, Tarjeta, etc.).
  - Registro y validación de la transacción Cashea (referencia / código de orden / código de autorización).
  - Soporte para dos modos de operación:
    1. **Modo Directo / API**: Comunicación con la API de Cashea (Merchant API) para generar y verificar la orden mediante QR/OTP.
    2. **Modo Asistido / Manual**: Validación mediante código de confirmación del cliente en la app de Cashea (ideal para contingencias o antes de credenciales de producción).
  - Trazabilidad en tabla dedicada `cashea_transacciones`.
- **Pagos Combinados Multidivisa**: Admisión de pagos mixtos (ej. Inicial Cashea en Pago Móvil + resto financiado por Cashea, o parte en USD efectivo y parte en Bolívares) con registro de la tasa aplicada y cálculo de vuelto/cambio.
- **Control Integral de Inventario y Auditoría Física**:
  - **Ajustes Directos de Entradas y Salidas**: Proceso documental con cabecera y detalle (`inventario_ajustes`, `inventario_ajuste_detalles`), filtrado por naturaleza (`ENTRADA` / `SALIDA`), selección de unidad principal o secundaria (conversión atómica), motivo y documento de soporte.
  - **Módulo de Conteo Físico y Ajuste de Inventario (Toma Física)**: Auditorías periódicas o sectorizadas (por marca/categoría), comparación en tiempo real entre stock teórico del sistema y stock físico contado con escáner (`F2`), cálculo de discrepancias (faltantes/sobrantes), valorización monetaria de diferencias y aplicación atómica de conciliación en inventario.
  - Trazabilidad inmutable en tabla `movimientos_inventario` y sesiones de auditoría en `inventario_conteos` e `inventario_conteo_detalles`.
- **Pistas de Auditoría Integral (Audit Trail)**: Trazabilidad exhaustiva de cambios en cualquier entidad del sistema (creación, edición o eliminación de productos, precios, ajustes de inventario, turnos de caja, anulaciones de venta y tasas de cambio), registrando usuario responsable, dirección IP, agente de usuario, URL y el diferencial exacto (`valores_anteriores` vs. `valores_nuevos` en JSON) en la tabla `auditorias`.
- **Caja y Arqueo Multimoneda**: Apertura con fondo inicial, registro de movimientos menores (ingresos/egresos), cierre ciego (Corte Z/X) con declaración de valores físicos por denominación/moneda y reporte de cobros Cashea.
- **Facturación Interna y Clientes**: Emisión de comprobantes internos (Ticket, Boleta, Factura) con correlativo automático, desglose de financiamiento Cashea, impresión térmica ESC/POS y PDF con QR verificador; directorio de clientes con límites de crédito e historial de consumo.
- **Dashboard y Reportes**: Indicadores de ventas diarias consolidadas en moneda base, desglose de ventas por método (incluyendo volumen colocado por Cashea), ticket promedio, margen bruto, productos más vendidos y exportación a PDF/Excel.

#### Fuera del Alcance (Out-of-Scope para futuras versiones)
- Sincronización offline completa mediante IndexedDB (el MVP opera en red local / intranet con reintentos).
- Integración directa con APIs fiscales gubernamentales (SUNAT, SENIAT, DIAN, etc.); la facturación del MVP opera con numeración y control fiscal interno.
- Cobro directo de las cuotas futuras de Cashea (las cuotas 1, 2 y 3 las cobra directamente la app Cashea al usuario final).
- Tienda virtual pública E-commerce B2C.

---

### 1.4 Métricas y KPIs
- **Tiempo de Respuesta P95 en Cobro POS**: $\le 300\text{ ms}$.
- **Tiempo de Autorización de Venta Cashea**: $\le 5\text{ segundos}$.
- **Tiempo de Encolar / Reanudar Venta en Espera**: $\le 1\text{ segundo}$.
- **Discrepancia en Inventario**: $< 0.5\%$.
- **Tiempo Medio de Espera en Caja**: $< 2\text{ minutos}$.
- **Tasa de Errores de Cierre de Caja (Corte Z)**: $< 1.0\%$.
- **Ventas Retenidas Recuperadas Exitosamente**: $> 90\%$.
- **Disponibilidad del Sistema (Uptime)**: $\ge 99.9\%$.

---

### 1.5 Historias de Usuario Críticas

#### HU-01: Venta Rápida Multipago Multimoneda en POS
- **Rol**: Cajero / Operador de POS.
- **Dado** que el cajero se encuentra en la pantalla del POS con la caja abierta y la tasa del día configurada ($1\text{ USD} = 85.00\text{ VES}$).
- **Cuando** escanea los códigos de barra de los productos (total: $\$30.00\text{ USD}$ / $2,550.00\text{ VES}$) y presiona `F4` (Cobrar).
- **Y** selecciona pagar $\$10.00\text{ USD}$ en efectivo y el restante en Bolívares ($1,700.00\text{ VES}$) por Pago Móvil.
- **Entonces** el sistema valida que el monto total cubra la venta, descuenta el stock en inventario dentro de una transacción ACID, emite el ticket ESC/POS con el desglose en ambas monedas y limpia la caja en menos de $300\text{ ms}$.

#### HU-02: Cobro de Venta Financiada con Cashea (Inicial en Tienda + Cuotas)
- **Rol**: Cajero / Operador de POS.
- **Dado** que un cliente en caja solicita pagar con **Cashea** una compra de $\$100.00\text{ USD}$.
- **Cuando** el cajero presiona la opción "Cobro Cashea" en el modal de cobro (`F4`), ingresa la cédula y teléfono del cliente, y selecciona el nivel (ej. Nivel 3: $40\%$ de inicial).
- **Y** el sistema calcula automáticamente:
  - Inicial a pagar en tienda: $\$40.00\text{ USD}$ (o su equivalente en Bs. $3,400.00\text{ VES}$).
  - Saldo financiado por Cashea: $\$60.00\text{ USD}$ en 3 cuotas quincenales de $\$20.00\text{ USD}$ c/u.
- **Y** el cliente paga los $\$40.00\text{ USD}$ de inicial mediante Pago Móvil / Tarjeta y se ingresa el código de autorización / referencia de Cashea.
- **Entonces** el sistema valida la transacción Cashea, descuenta los productos en inventario, emite el comprobante indicando el detalle de las cuotas Cashea y registra el pago en `pagos_venta` y `cashea_transacciones`.

#### HU-03: Pausar Venta en Cola de Espera y Atender Fila Continua (Ticket Parking)
- **Rol**: Cajero / Operador de POS.
- **Contexto**: Hay 3 clientes en fila esperando en la caja registradora.
- **Dado** que el cajero tiene al **Cliente 1** en el mostrador con 5 productos ya escaneados por un subtotal de $\$45.00\text{ USD}$, y justo al momento de cobrar, el cliente se percata de que olvidó un artículo y se retira momentáneamente a buscarlo a las estanterías.
- **Cuando** el cajero presiona la tecla `F8` ("Pausar / Poner en Cola") o hace clic en el botón de la interfaz.
- **Y** el sistema solicita opcionalmente una nota o identificador rápido (por defecto asigna `Ticket #1 - $45.00 (5 items)`), guarda íntegramente la venta en la **Cola de Espera (Pinia + LocalStorage)** y limpia la pantalla del POS de inmediato en menos de 300 ms.
- **Entonces** el cajero atiende inmediatamente al **Cliente 2**, quien solo lleva 1 artículo de $\$5.00\text{ USD}$, escanea el código, cobra en efectivo o pago móvil y emite su comprobante en menos de 10 segundos sin demorar la fila.
- **Y cuando** el **Cliente 1** regresa al mostrador con su artículo faltante:
  - El cajero presiona `Shift + F8` o hace clic directamente en el badge de la barra superior `[ ⏸️ Ticket #1: $45.00 (5 ítems) ]`.
  - El sistema restaura instantáneamente la cesta del Cliente 1 en el punto exacto donde quedó.
  - El cajero escanea el nuevo producto, el total se actualiza automáticamente a $\$52.00\text{ USD}$ y se procede con el cobro habitual.
- **Y si** hubiera un **Cliente 3**, el cajero puede volver a encolar o gestionar hasta N ventas en cola simultáneamente sin perder datos ni bloquear la caja.

#### HU-04: Cierre Ciego de Caja Multimoneda (Corte Z)
- **Rol**: Cajero / Supervisor.
- **Dado** que finaliza el turno del cajero y accede a "Cierre de Caja".
- **Cuando** ingresa los montos físicos contados (efectivo en USD, efectivo en VES, vouchers de tarjeta, transferencias) sin ver los saldos teóricos.
- **Entonces** el sistema calcula el saldo teórico esperado por moneda y método (incluyendo iniciales cobradas y registros Cashea), detecta cualquier diferencia y emite el comprobante de Corte Z.

#### HU-05: Proceso de Ajustes de Inventario (Cabecera y Detalle con Selección por Naturaleza E/S)
- **Rol**: Administrador / Encargado de Almacén.
- **Dado** que se requiere registrar un ajuste de inventario (recepción de compras imprevistas, mermas por rotura o vencimiento, o consumo interno de la tienda).
- **Cuando** el usuario crea un nuevo documento de ajuste, selecciona la naturaleza de la operación ("ENTRADA" o "SALIDA").
- **Y** el sistema filtra de forma dinámica el catálogo de tipos de movimiento (`tipos_movimiento_inventario`), permitiendo seleccionar únicamente aquellos correspondientes a la naturaleza elegida (ej. `ENTRADA_COMPRA` o `ENTRADA_AJUSTE` si es entrada; `SALIDA_MERMA`, `SALIDA_CONSUMO` o `SALIDA_AJUSTE` si es salida).
- **Y** el usuario redacta el motivo general, adjunta el documento de referencia opcional y añade múltiples líneas de productos en la grilla de detalle, especificando para cada uno: producto, unidad de medida (principal o secundaria con conversión atómica), cantidad y observaciones.
- **Entonces** el sistema valida que las salidas cuenten con stock suficiente, genera el comprobante correlativo `AJU-YYYY-XXXX` en `inventario_ajustes`, almacena cada ítem en `inventario_ajuste_detalles`, actualiza las existencias en `productos` y asienta automáticamente cada movimiento en la tabla de auditoría `movimientos_inventario`.

#### HU-06: Conteo Físico y Conciliación de Inventario (Toma Física)
- **Rol**: Supervisor / Auditor de Inventarios.
- **Dado** que se realiza una auditoría periódica (general o filtrada por departamento/marca).
- **Cuando** el supervisor inicia una nueva sesión de conteo ("Toma Física General"), el sistema congela el `stock_teorico` de los productos seleccionados y habilita la pantalla de captura rápida con escáner de código de barras (`F2`).
- **Y** los operadores ingresan o pistolean los productos registrando el `stock_fisico` real existente en los estantes.
- **Y** el sistema presenta en tiempo real la grilla comparativa con indicadores de discrepancia (`diferencia = stock_fisico - stock_teorico`), alertando en verde los ítems cuadrados, en azul los sobrantes y en rojo los faltantes, con su respectiva valorización monetaria en USD ($) y VES (Bs.).
- **Entonces** tras verificar las justificaciones u observaciones, el supervisor presiona "Aplicar Ajuste de Conteo". El sistema ejecuta una transacción atómica que actualiza las existencias de todos los productos discrepantes, genera los registros correspondientes en `movimientos_inventario` (tipo `AJUSTE_CONTEO`) y cierra la sesión de auditoría con su respectiva acta imprimible en PDF.

#### HU-07: Trazabilidad y Pistas de Auditoría de Acciones de Usuario
- **Rol**: Administrador / Auditor de Seguridad.
- **Dado** que cualquier usuario crea, modifica o elimina un registro crítico (precios o stock de productos, anulación de tickets, apertura/cierre de turnos de caja, ajustes de inventario o tasas de cambio).
- **Cuando** la acción se ejecuta mediante el API o la interfaz web.
- **Entonces** el sistema captura automáticamente un registro inmutable en la tabla `auditorias` con el `id_usuario`, el módulo afectado (`modulo`), la acción realizada (`accion`), la tabla (`tabla_afectada`), el identificador del registro (`id_registro_afectado`), la dirección IP (`ip_direccion`), el navegador/dispositivo (`user_agent`), la URL invocada y el diferencial exacto de datos en formato JSON (`valores_anteriores` vs. `valores_nuevos`).
- **Y cuando** el administrador accede a la sección "Pistas de Auditoría", puede filtrar por rango de fechas, usuario o módulo, y visualizar una comparativa en vivo (diff) que resalta en rojo los valores anteriores y en verde los valores modificados o agregados.

---

## SECCIÓN 2: TECHNICAL REQUIREMENTS DOCUMENT (TRD)

### 2.1 Pila Tecnológica
- **Backend**: Laravel 11 / PHP 8.2+ con arquitectura en capas (Controllers, FormRequests, Services, Models).
- **Starter Kit & Autenticación**: Laravel Breeze con stack Vue 3 + Inertia.js y Laravel Sanctum.
- **Frontend**: Vue.js 3 (Composition API con `<script setup>`), Pinia para gestión de estado reactivo del POS, Tailwind CSS para el diseño visual.
- **Base de Datos**: MySQL 8.0 (motor InnoDB con restricciones de clave foránea y constraints CHECK).
- **Integración Cashea**: Módulo de servicio `CasheaService.php` preparado para:
  - Consumo de API REST oficial de Cashea (OAuth / API Key, endpoints de creación y confirmación de orden).
  - Modo asistido/manual para validación de código de referencia Cashea de 6 a 10 dígitos cuando no hay conectividad externa.
- **Manejo de Impresión y Documentos**: Formato de texto ESC/POS para tickets térmicos (58mm / 80mm) con detalle Cashea y generación de PDF con código QR embebido.

### 2.2 Principios de Estilo y Código del Proyecto
- **Formato de Llaves (Estilo Allman / BSD)**:
  - Llave de apertura `{` siempre en una nueva línea directamente debajo de la declaración (clases, funciones, `if`, bucles).
  - Alineada verticalmente con la llave de cierre `}`.
- **Convenio de Nombres**:
  - Variables compuestas, métodos, funciones y clases en **PascalCase** (ejemplo: `ActualizarInventario`, `TasaCambio`, `CasheaTransaccion`, `CalcularPlanCuotas`).
  - Palabras simples individuales en **minúscula** (ejemplo: `monedas`, `productos`, `usuarios`, `inventario`, `cashea`, `cuotas`).

---

## SECCIÓN 3: PROPUESTA DE DISEÑO UI/UX

### 3.1 Paleta de Colores
| Uso | Nombre | Hex | Aplicación |
|---|---|---|---|
| **Primario** | Azul Corporativo | `#1E40AF` | Cabeceras, botones principales, bordes activos |
| **Secundario** | Slate Gris | `#334155` | Menús, barra lateral, títulos secundarios |
| **Acento** | Esmeralda POS | `#059669` | Botón Cobrar (F4), badges de éxito, stock óptimo |
| **Cashea Brand** | Amarillo / Ocre Cashea | `#F59E0B` | Botón Cashea en checkout, badges de cuotas, cards Cashea |
| **Fondo** | Neutral Claro | `#F8FAFC` | Fondo de pantallas y contenedores principales |
| **Alerta / Error** | Rojo Carmesí | `#DC2626` | Stock crítico, cancelar venta, errores de validación |
| **Advertencia** | Ámbar | `#D97706` | Ventas en espera, alertas de vencimiento próximo |
| **Informativo** | Cyan | `#0891B2` | Tooltips, datos de cliente, tasas de cambio |

### 3.2 Layout del Punto de Venta (POS) y Barra de Cola de Ventas (Ticket Parking)
El diseño del POS está estructurado para maximizar la velocidad operativa del cajero en momentos de alto tráfico:
- **Barra Superior de Cola de Espera (Ticket Parking Bar)**:
  - Ubicada directamente en la cabecera del carrito.
  - Muestra la venta activa y pestañas/badges interactivos con las ventas pausadas en cola:
    `[ 🟢 Venta Activa (Ticket #2) ]` `[ ⏸️ En Cola: Ticket #1 (5 items - $45.00) ]` `[ ➕ Nueva Venta (F3) ]`.
  - Contador de ventas en cola: Permite saber cuántos clientes están en pausa.
  - Atajos de teclado dedicados:
    - `F8`: Pausar venta actual y colocarla en cola de espera (con o sin nota/alias).
    - `Shift + F8`: Desplegar selector de cola de ventas o alternar al ticket en cola anterior.
    - `Ctrl + Supr`: Descartar un ticket en cola si el cliente desiste definitivamente.
- **Columna Izquierda (65%)**:
  - Buscador predictivo ultrarrápido con selector de lector de código de barras (`F2`).
  - Filtros por categoría y parrilla táctil de productos con indicación de existencias y precios duales (USD / VES).
- **Columna Derecha (35%)**:
  - Carrito interactivo con cantidades dinámicas, descuentos por ítem y totalizadores.
  - Botón prominente de retención de venta: **`PAUSAR / ENCOLAR (F8)`**.
  - Botón principal de cobro: **`COBRAR (F4)`**.

### 3.3 Modal de Cobro con Soporte Cashea
En el modal de cobro (`F4`), se incluye la opción destacada **"Pagar con Cashea"**:
- **Simulador de Cuotas en Vivo**:
  - Selector de % Inicial: `[ 40% (Nivel 3+) ]` `[ 50% (Nivel 2) ]` `[ 60% (Nivel 1) ]`.
  - Muestra visual clara:
    - 💰 **Inicial a pagar hoy**: `$40.00 USD` / `3,400.00 VES`.
    - 📅 **3 Cuotas quincenales de**: `$20.00 USD` c/u (pagaderas en app Cashea).
- **Formulario de Inicial**: Permite pagar la inicial en efectivo USD, pago móvil VES, tarjeta de débito, etc.
- **Campo de Validación Cashea**: Input para ingresar la Referencia / Código de Aprobación de la app Cashea (con botón de verificación rápida).

---

## SECCIÓN 4: ARQUITECTURA DE BASE DE DATOS (MYSQL 8.0)

### 4.1 Modelo Relacional con Catálogo Avanzado, Multimoneda y Cashea

```
+---------------------+         +---------------------+         +---------------------+
|      USUARIOS       |         |     CAJA_TURNOS     |         |       VENTAS        |
+---------------------+         +---------------------+         +---------------------+
| PK id_usuario       |<-------1| PK id_caja_turno    |<-------1| PK id_venta         |
|    nombre           |    │    | FK id_usuario       |         | FK id_caja_turno    |
|    email            |    │    |    monto_inicial    |         | FK id_cliente       |
|    rol              |    │    |    estado           |         | FK id_moneda        |
+----------┬----------+    │    +---------------------+         |    tasa_cambio      |
           │ 1             │                                    |    total            |
           │               │                                    +----------┬----------+
           │ N             │                                               │ 1
+----------┴----------+    │    +---------------------+                    │
|HISTORICO_TASAS_CAMB |    │    |      CLIENTES       |                    │ N
+---------------------+    │    +---------------------+                    │
| PK id_historico_tasa|    │    | PK id_cliente       |         +----------┴----------+
| FK id_moneda        |<─┐ │    |    identificacion   |         |   VENTA_DETALLES    |
| FK id_usuario       |  │ │    |    limite_credito   |         +---------------------+
|    tasa_anterior    |  │ │    |    saldo_pendiente  |         | PK id_venta_detalle |
|    tasa_nueva       |  │ │    +---------------------+         | FK id_venta         |
|    observaciones    |  │ │                                    | FK id_producto      |
+---------------------+  │ │    +---------------------+         |    cantidad         |
                         │ │    |     PAGOS_VENTA     |         |    precio_unitario  |
+---------------------+  │ │    +---------------------+         +----------┬----------+
|       MONEDAS       |  │ │    | PK id_pago_venta    |                    │ N
+---------------------+  │ │    | FK id_venta         |                    │
| PK id_moneda        |──┘ │    | FK id_metodo_pago   |                    │ 1
|    codigo (USD..)   |<───┼────┤ FK id_moneda        |         +----------┴----------+
|    tasa_cambio      |    │    |    monto            |         |      PRODUCTOS      |
|    es_principal     |    │    |    tasa_cambio      |         +---------------------+
+----------┬----------+    │    |    monto_base       |         | PK id_producto      |
           │ 1             │    +---------------------+         |    sku, nombre      |
           │               │                                    |    modelo           |
           │ N             │    +---------------------+         | FK id_marca         |
+----------┴----------+    │    |  MARCAS_PRODUCTOS   |         | FK id_categoria     |
|    METODOS_PAGO     |    │    +---------------------+         | FK id_unidad        |
+---------------------+    │    | PK id_marca         |1        | FK id_unidad_secund |
| PK id_metodo_pago   |<───┘    +----------┬----------+         |    equivalencia_unid|
| FK id_moneda        |                    │                    |    equivalencia_sec |
|    nombre           |                    ▼                    |    imagen_principal |
|    codigo           |          [Relaciones a PRODUCTOS]       +----------┬----------+
|    tipo             |                    ▲                               │ 1
+---------------------+         +----------┴----------+                    │ N
                                |CLASIFICACION_PRODUC |                    │
+---------------------+         +---------------------+         +----------┴----------+
|  UNIDADES_PRODUCTOS |1       1| PK id_categoria     |         |  MOVIMIENTOS_INVENT |
+---------------------+         +---------------------+         +---------------------+
| PK id_unidad        |                                         | PK id_movimiento_inv|
+----------┬----------+         +---------------------+         | FK id_producto      |
           │                    | PRODUCTOS_IMAGENES  |         | FK id_usuario       |
           └───────────────────►|---------------------|         | FK id_tipo_movimient|
                                | PK id_producto_img  |         +----------┬────▲-----+
                                |    ruta_imagen      |                    │    │ N
                                |    es_principal     |                    │    │ 1
                                +---------------------+                    │ +──┴────────────────+
                                                                           │ | TIPOS_MOVIMIENTO  |
                                                                           │ +-------------------+
                                                                           │ |PK id_tipo_movimien|
                                                                           │ |   codigo, nombre  |
                                                                           │ |   naturaleza (E/S)|
                                                                           │ +──┬────────────────+
                                                                           │    │ 1
                                       ┌───────────────────────────────────┼────┘
                                       ▼                                   ▼
                            +---------------------+             +---------------------+
                            | INVENTARIO_AJUSTES  |             |  INVENTARIO_CONTEOS |
                            +---------------------+             +---------------------+
                            | PK id_ajuste        |<─┐          | PK id_conteo        |<─┐
                            | FK id_usuario       |  │          | FK id_usuario       |  │
                            | FK id_tipo_movimient|  │          |    codigo_conteo    |  │
                            |    codigo_ajuste    |  │          |    estado           |  │
                            |    motivo           |  │          +---------------------+  │
                            +---------------------+  │                     │ 1           │
                                       │ 1           │                     │             │
                                       │ N           │                     │ N           │
                            +----------┴----------+  │          +----------┴----------+  │
                            |INVENTARIO_AJUSTE_DET|  │          |INVENTARIO_CONTEO_DET|  │
                            +---------------------+  │          +---------------------+  │
                            | PK id_ajuste_detalle|  │          | PK id_conteo_detalle|  │
                            | FK id_ajuste        |──┘          | FK id_conteo        |──┘
                            | FK id_producto      |             | FK id_producto      |
                            | FK id_unidad        |             |    stock_teorico    |
                            |    cantidad_base    |             |    stock_fisico     |
                            +---------------------+             |    diferencia       |
                                                                +---------------------+
                            +---------------------+
                            |     AUDITORIAS      |
                            +---------------------+
                            | PK id_auditoria     |
                            | FK id_usuario       |<──────(Auditoría Global de Acciones)
                            |    modulo, accion   |
                            |    tabla_afectada   |
                            |    id_registro_afect|
                            |    valores_anterior |
                            |    valores_nuevos   |
                            |    ip_direccion     |
                            +---------------------+
```

---

### 4.2 Código SQL DDL (MySQL 8.0)

```sql
CREATE DATABASE IF NOT EXISTS facil_shop_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE facil_shop_db;

-- 1. Tabla: usuarios
CREATE TABLE usuarios (
    id_usuario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('superadmin', 'admin', 'cajero') NOT NULL DEFAULT 'cajero',
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_usuarios_email (email)
) ENGINE=InnoDB;

-- 2. Tabla: monedas (Multimoneda y Tasas)
CREATE TABLE monedas (
    id_moneda BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE, -- USD, VES, EUR
    nombre VARCHAR(50) NOT NULL,        -- Dólar Estadounidense, Bolívar Digital, Euro
    simbolo VARCHAR(10) NOT NULL,       -- $, Bs., €
    tasa_cambio DECIMAL(16, 4) NOT NULL DEFAULT 1.0000, -- Respecto a la moneda base
    es_principal BOOLEAN NOT NULL DEFAULT FALSE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_monedas_codigo (codigo)
) ENGINE=InnoDB;

-- 3. Tabla: historico_tasas_cambio (Trazabilidad y auditoría cronológica de cotizaciones)
CREATE TABLE historico_tasas_cambio (
    id_historico_tasa BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_moneda BIGINT UNSIGNED NOT NULL,
    id_usuario BIGINT UNSIGNED NOT NULL,
    tasa_anterior DECIMAL(16, 4) NOT NULL,
    tasa_nueva DECIMAL(16, 4) NOT NULL,
    observaciones VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_moneda) REFERENCES monedas(id_moneda) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_historico_moneda_fecha (id_moneda, created_at)
) ENGINE=InnoDB;

-- 4. Tabla: metodos_pago (Métodos de pago dinámicos vinculados a una moneda)
CREATE TABLE metodos_pago (
    id_metodo_pago BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,              -- Ej: Efectivo USD, Efectivo Bolívares, Pago Móvil, Punto de Venta, Zelle, Cashea, Crédito
    codigo VARCHAR(50) NOT NULL UNIQUE,       -- Ej: EFECTIVO_USD, EFECTIVO_VES, PAGO_MOVIL, PUNTO_VENTA, ZELLE, CASHEA, CREDITO
    id_moneda BIGINT UNSIGNED NOT NULL,        -- Relación obligatoria con la moneda operativa del método
    tipo ENUM('EFECTIVO', 'DIGITAL', 'TRANSFERENCIA', 'TARJETA', 'CREDITO', 'FINANCIAMIENTO') NOT NULL DEFAULT 'EFECTIVO',
    requiere_referencia BOOLEAN NOT NULL DEFAULT FALSE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_moneda) REFERENCES monedas(id_moneda) ON DELETE RESTRICT,
    INDEX idx_metodos_pago_moneda (id_moneda)
) ENGINE=InnoDB;

-- 5. Tabla: clientes
CREATE TABLE clientes (
    id_cliente BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificacion VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) NULL,
    email VARCHAR(150) NULL,
    limite_credito DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    saldo_pendiente DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_limite_credito CHECK (limite_credito >= 0.00),
    INDEX idx_clientes_identificacion (identificacion)
) ENGINE=InnoDB;

-- 6. Tabla: marcas_productos
CREATE TABLE marcas_productos (
    id_marca BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marcas_nombre (nombre)
) ENGINE=InnoDB;

-- 7. Tabla: clasificacion_productos (Categorías / Departamentos)
CREATE TABLE clasificacion_productos (
    id_categoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    id_categoria_padre BIGINT UNSIGNED NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_categoria_padre) REFERENCES clasificacion_productos(id_categoria) ON DELETE SET NULL,
    INDEX idx_clasificacion_nombre (nombre)
) ENGINE=InnoDB;

-- 8. Tabla: unidades_productos (Unidades de Medida)
CREATE TABLE unidades_productos (
    id_unidad BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,          -- Ej: Unidad, Caja, Kilogramo, Bulto, Litro, Metro
    abreviatura VARCHAR(10) NOT NULL UNIQUE,     -- Ej: UND, CJ, KG, BLT, LT, M
    permite_decimales BOOLEAN NOT NULL DEFAULT FALSE,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 9. Tabla: productos (Catálogo con Modelo, Marcas, Categorías, Unidades y Equivalencias)
CREATE TABLE productos (
    id_producto BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    codigo_barras VARCHAR(100) NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    modelo VARCHAR(100) NULL,
    descripcion TEXT NULL,
    id_marca BIGINT UNSIGNED NULL,
    id_categoria BIGINT UNSIGNED NULL,
    id_unidad BIGINT UNSIGNED NOT NULL,                    -- Unidad principal (ej: Caja, Bulto, Kilo)
    id_unidad_secundaria BIGINT UNSIGNED NULL,            -- Unidad secundaria (ej: Pieza, Unidad)
    equivalencia_unidad DECIMAL(12, 4) NOT NULL DEFAULT 1.0000, -- Factor unidad principal
    equivalencia_unidad_secundaria DECIMAL(12, 4) NOT NULL DEFAULT 1.0000, -- Factor unidad secundaria (ej: 1 caja = 12 unidades)
    precio_costo DECIMAL(14, 4) NOT NULL,                 -- En moneda base (ej. USD)
    precio_venta DECIMAL(14, 4) NOT NULL,                 -- En moneda base
    stock_actual INT NOT NULL DEFAULT 0,
    stock_minimo INT NOT NULL DEFAULT 5,
    imagen_principal VARCHAR(255) NULL,                   -- Ruta/URL de la imagen destacada
    fecha_vencimiento DATE NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_marca) REFERENCES marcas_productos(id_marca) ON DELETE SET NULL,
    FOREIGN KEY (id_categoria) REFERENCES clasificacion_productos(id_categoria) ON DELETE SET NULL,
    FOREIGN KEY (id_unidad) REFERENCES unidades_productos(id_unidad) ON DELETE RESTRICT,
    FOREIGN KEY (id_unidad_secundaria) REFERENCES unidades_productos(id_unidad) ON DELETE RESTRICT,
    CONSTRAINT chk_precio_venta CHECK (precio_venta >= precio_costo),
    CONSTRAINT chk_stock_actual CHECK (stock_actual >= 0),
    INDEX idx_productos_sku (sku),
    INDEX idx_productos_codigo_barras (codigo_barras),
    INDEX idx_productos_marca (id_marca),
    INDEX idx_productos_categoria (id_categoria)
) ENGINE=InnoDB;

-- 10. Tabla: productos_imagenes (Galería de múltiples imágenes por producto)
CREATE TABLE productos_imagenes (
    id_producto_imagen BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_producto BIGINT UNSIGNED NOT NULL,
    ruta_imagen VARCHAR(255) NOT NULL,
    es_principal BOOLEAN NOT NULL DEFAULT FALSE,
    orden INT NOT NULL DEFAULT 0,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
    INDEX idx_imagenes_producto (id_producto, es_principal)
) ENGINE=InnoDB;

-- 11. Tabla: caja_turnos
CREATE TABLE caja_turnos (
    id_caja_turno BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario BIGINT UNSIGNED NOT NULL,
    monto_inicial DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    monto_final_teorico DECIMAL(14, 2) NULL,
    monto_final_declarado DECIMAL(14, 2) NULL,
    diferencia DECIMAL(14, 2) NULL,
    estado ENUM('ABIERTA', 'CERRADA') NOT NULL DEFAULT 'ABIERTA',
    fecha_apertura TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    observaciones TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_caja_turnos_estado (id_usuario, estado)
) ENGINE=InnoDB;

-- 12. Tabla: ventas
CREATE TABLE ventas (
    id_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_caja_turno BIGINT UNSIGNED NOT NULL,
    id_cliente BIGINT UNSIGNED NOT NULL,
    id_moneda BIGINT UNSIGNED NOT NULL,
    tasa_cambio DECIMAL(16, 4) NOT NULL DEFAULT 1.0000,
    numero_comprobante VARCHAR(50) NOT NULL UNIQUE,
    tipo_comprobante ENUM('TICKET', 'BOLETA', 'FACTURA') NOT NULL DEFAULT 'TICKET',
    subtotal DECIMAL(14, 2) NOT NULL,
    descuento_total DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    impuesto DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(14, 2) NOT NULL,
    total_moneda_base DECIMAL(14, 2) NOT NULL,
    estado ENUM('COMPLETADA', 'ANULADA') NOT NULL DEFAULT 'COMPLETADA',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_caja_turno) REFERENCES caja_turnos(id_caja_turno) ON DELETE RESTRICT,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE RESTRICT,
    FOREIGN KEY (id_moneda) REFERENCES monedas(id_moneda) ON DELETE RESTRICT,
    INDEX idx_ventas_comprobante (numero_comprobante),
    INDEX idx_ventas_fecha (created_at)
) ENGINE=InnoDB;

-- 13. Tabla: venta_detalles
CREATE TABLE venta_detalles (
    id_venta_detalle BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venta BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(14, 4) NOT NULL,
    descuento DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(14, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT,
    CONSTRAINT chk_cantidad_positiva CHECK (cantidad > 0)
) ENGINE=InnoDB;

-- 14. Tabla: pagos_venta (Multipago con Métodos de Pago y Moneda)
CREATE TABLE pagos_venta (
    id_pago_venta BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venta BIGINT UNSIGNED NOT NULL,
    id_metodo_pago BIGINT UNSIGNED NOT NULL,
    id_moneda BIGINT UNSIGNED NOT NULL,
    monto DECIMAL(14, 2) NOT NULL,            -- Monto en la moneda del método de pago
    tasa_cambio DECIMAL(16, 4) NOT NULL,      -- Tasa aplicada
    monto_base DECIMAL(14, 2) NOT NULL,       -- Equivalente convertido a moneda base
    referencia VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_metodo_pago) REFERENCES metodos_pago(id_metodo_pago) ON DELETE RESTRICT,
    FOREIGN KEY (id_moneda) REFERENCES monedas(id_moneda) ON DELETE RESTRICT,
    INDEX idx_pagos_venta_metodo (id_metodo_pago),
    CONSTRAINT chk_monto_pago CHECK (monto > 0.00)
) ENGINE=InnoDB;

-- 15. Tabla: cashea_transacciones (Registro y financiamiento Cashea)
CREATE TABLE cashea_transacciones (
    id_cashea_transaccion BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_venta BIGINT UNSIGNED NOT NULL,
    id_cliente BIGINT UNSIGNED NULL,
    cedula_cliente VARCHAR(20) NOT NULL,
    telefono_cliente VARCHAR(30) NULL,
    referencia_cashea VARCHAR(100) NOT NULL UNIQUE,
    monto_total DECIMAL(14, 2) NOT NULL,
    porcentaje_inicial DECIMAL(5, 2) NOT NULL DEFAULT 40.00,
    monto_inicial DECIMAL(14, 2) NOT NULL,
    monto_financiado DECIMAL(14, 2) NOT NULL,
    numero_cuotas INT NOT NULL DEFAULT 3,
    monto_cuota DECIMAL(14, 2) NOT NULL,
    estado ENUM('PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA') NOT NULL DEFAULT 'APROBADA',
    modo ENUM('MANUAL', 'API') NOT NULL DEFAULT 'MANUAL',
    codigo_autorizacion VARCHAR(50) NULL,
    payload JSON NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venta) REFERENCES ventas(id_venta) ON DELETE CASCADE,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON DELETE SET NULL,
    INDEX idx_cashea_referencia (referencia_cashea),
    INDEX idx_cashea_cedula (cedula_cliente)
) ENGINE=InnoDB;

-- 16. Tabla: tipos_movimiento_inventario (Catálogo dinámico de tipos de movimiento de inventario)
CREATE TABLE tipos_movimiento_inventario (
    id_tipo_movimiento BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,          -- ENTRADA_COMPRA, ENTRADA_AJUSTE, SALIDA_VENTA, SALIDA_MERMA, SALIDA_CONSUMO, SALIDA_AJUSTE, AJUSTE_CONTEO, DEVOLUCION
    nombre VARCHAR(100) NOT NULL,               -- Entrada por Compra, Salida por Merma, etc.
    naturaleza ENUM('ENTRADA', 'SALIDA', 'AJUSTE') NOT NULL, -- Determina si suma, resta o concilia existencias
    descripcion TEXT NULL,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tipos_movimiento_codigo (codigo)
) ENGINE=InnoDB;

-- 17. Tabla: movimientos_inventario (Trazabilidad inmutable de inventario)
CREATE TABLE movimientos_inventario (
    id_movimiento_inventario BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_producto BIGINT UNSIGNED NOT NULL,
    id_usuario BIGINT UNSIGNED NOT NULL,
    id_tipo_movimiento BIGINT UNSIGNED NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    nuevo_stock INT NOT NULL,
    motivo VARCHAR(255) NULL,
    documento_referencia VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_tipo_movimiento) REFERENCES tipos_movimiento_inventario(id_tipo_movimiento) ON DELETE RESTRICT,
    INDEX idx_movimientos_producto (id_producto, created_at),
    INDEX idx_movimientos_tipo (id_tipo_movimiento)
) ENGINE=InnoDB;

-- 18. Tabla: cashea_config (Configuración de integración Cashea)
CREATE TABLE cashea_config (
    id_cashea_config BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modo_operacion ENUM('MANUAL', 'API_SANDBOX', 'API_PRODUCCION') NOT NULL DEFAULT 'MANUAL',
    api_key VARCHAR(255) NULL,
    api_secret VARCHAR(255) NULL,
    merchant_id VARCHAR(100) NULL,
    porcentaje_inicial_defecto DECIMAL(5, 2) NOT NULL DEFAULT 40.00,
    cuotas_defecto INT NOT NULL DEFAULT 3,
    activo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 19. Tabla: inventario_conteos (Sesiones de auditoría y toma física de inventario)
CREATE TABLE inventario_conteos (
    id_conteo BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_conteo VARCHAR(50) NOT NULL UNIQUE,          -- Ej: CNT-2026-0001
    id_usuario BIGINT UNSIGNED NOT NULL,                -- Auditor / Supervisor responsable
    descripcion VARCHAR(255) NULL,                     -- Ej: Conteo General Almacén Central
    estado ENUM('BORRADOR', 'EN_PROCESO', 'APLICADO', 'CANCELADO') NOT NULL DEFAULT 'BORRADOR',
    fecha_inicio TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    total_items_contados INT NOT NULL DEFAULT 0,
    total_diferencia_unidades INT NOT NULL DEFAULT 0,
    total_diferencia_costo DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    INDEX idx_conteos_estado (estado),
    INDEX idx_conteos_codigo (codigo_conteo)
) ENGINE=InnoDB;

-- 20. Tabla: inventario_conteo_detalles (Detalle y conciliación de stock por producto)
CREATE TABLE inventario_conteo_detalles (
    id_conteo_detalle BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_conteo BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    stock_teorico INT NOT NULL,               -- Stock del sistema al momento de la auditoría
    stock_fisico INT NOT NULL,                -- Cantidad real contada
    diferencia INT NOT NULL,                  -- stock_fisico - stock_teorico (+ sobrante, - faltante)
    costo_unitario DECIMAL(14, 4) NOT NULL,   -- Costo en moneda base para valorizar
    valor_diferencia DECIMAL(14, 2) NOT NULL, -- diferencia * costo_unitario
    observaciones VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_conteo) REFERENCES inventario_conteos(id_conteo) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT,
    INDEX idx_conteo_detalles_producto (id_producto)
) ENGINE=InnoDB;

-- 21. Tabla: inventario_ajustes (Cabecera de ajustes directos de inventario con tipo de movimiento)
CREATE TABLE inventario_ajustes (
    id_ajuste BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_ajuste VARCHAR(50) NOT NULL UNIQUE,          -- Ej: AJU-2026-0001
    id_usuario BIGINT UNSIGNED NOT NULL,                -- Usuario que registra el ajuste
    id_tipo_movimiento BIGINT UNSIGNED NOT NULL,        -- Tipo de movimiento según naturaleza (ENTRADA o SALIDA)
    motivo VARCHAR(255) NOT NULL,                       -- Justificación del ajuste
    documento_referencia VARCHAR(100) NULL,             -- Factura, comprobante, acta
    estado ENUM('BORRADOR', 'APLICADO', 'ANULADO') NOT NULL DEFAULT 'APLICADO',
    fecha_ajuste TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total_items INT NOT NULL DEFAULT 0,
    total_costo DECIMAL(14, 2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE RESTRICT,
    FOREIGN KEY (id_tipo_movimiento) REFERENCES tipos_movimiento_inventario(id_tipo_movimiento) ON DELETE RESTRICT,
    INDEX idx_ajustes_codigo (codigo_ajuste),
    INDEX idx_ajustes_tipo (id_tipo_movimiento),
    INDEX idx_ajustes_estado (estado)
) ENGINE=InnoDB;

-- 22. Tabla: inventario_ajuste_detalles (Líneas de productos asociadas al ajuste)
CREATE TABLE inventario_ajuste_detalles (
    id_ajuste_detalle BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_ajuste BIGINT UNSIGNED NOT NULL,
    id_producto BIGINT UNSIGNED NOT NULL,
    id_unidad BIGINT UNSIGNED NOT NULL,                 -- Unidad seleccionada (principal o secundaria)
    cantidad DECIMAL(12, 4) NOT NULL,                   -- Cantidad en la unidad seleccionada
    cantidad_base INT NOT NULL,                         -- Cantidad convertida a unidad base de inventario
    costo_unitario DECIMAL(14, 4) NOT NULL,             -- Costo en moneda base
    costo_total DECIMAL(14, 2) NOT NULL,                -- cantidad_base * costo_unitario
    stock_anterior INT NOT NULL,                        -- Existencias antes de aplicar el ajuste
    nuevo_stock INT NOT NULL,                           -- Existencias tras aplicar el ajuste
    observaciones VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_ajuste) REFERENCES inventario_ajustes(id_ajuste) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE RESTRICT,
    FOREIGN KEY (id_unidad) REFERENCES unidades_productos(id_unidad) ON DELETE RESTRICT,
    INDEX idx_ajuste_detalles_producto (id_producto)
) ENGINE=InnoDB;

-- 23. Tabla: auditorias (Pistas de auditoría integral y registro de cambios)
CREATE TABLE auditorias (
    id_auditoria BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_usuario BIGINT UNSIGNED NULL,                  -- Usuario que ejecutó la acción (NULL para eventos de sistema)
    modulo VARCHAR(50) NOT NULL,                      -- PRODUCTOS, VENTAS, INVENTARIO, AJUSTES, CONTEOS, MONEDAS, CAJA, AUTH
    accion VARCHAR(50) NOT NULL,                      -- CREAR, ACTUALIZAR, ELIMINAR, APLICAR, ANULAR, LOGIN, LOGOUT
    tabla_afectada VARCHAR(100) NOT NULL,             -- Nombre de la tabla afectada en base de datos
    id_registro_afectado BIGINT UNSIGNED NULL,        -- ID del registro afectado (ej: id_producto, id_venta, etc.)
    valores_anteriores JSON NULL,                     -- Snapshot de datos previo a la acción (en formato JSON)
    valores_nuevos JSON NULL,                         -- Snapshot de datos posterior a la acción o campos modificados
    ip_direccion VARCHAR(45) NULL,                    -- Dirección IP del cliente (IPv4 o IPv6)
    user_agent VARCHAR(255) NULL,                     -- Navegador / Dispositivo
    url VARCHAR(255) NULL,                            -- Ruta / Endpoint invocado
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_auditorias_usuario (id_usuario),
    INDEX idx_auditorias_modulo_accion (modulo, accion),
    INDEX idx_auditorias_tabla_registro (tabla_afectada, id_registro_afectado),
    INDEX idx_auditorias_fecha (created_at)
) ENGINE=InnoDB;
```

---

## SECCIÓN 5: ENDPOINTS DE LA API REST

### 5.1 Monedas, Tasas de Cambio y Métodos de Pago
- `GET /api/v1/currencies` - Listar monedas activas y tasas actuales.
- `POST /api/v1/currencies/rates` - Actualizar cotizaciones de monedas (registra automáticamente en `historico_tasas_cambio`).
- `GET /api/v1/currencies/{id_moneda}/history` - Consultar historial cronológico de cotizaciones de una moneda.
- `GET /api/v1/payment-methods` - Listar métodos de pago activos con su moneda asignada (`id_moneda`).

### 5.2 Módulo Cashea BNPL
- `POST /api/v1/cashea/simulate` - Calcular desglose de inicial y cuotas para un monto dado.
- `POST /api/v1/cashea/verify` - Verificar y pre-autorizar orden Cashea (vía API o validación de referencia).
- `GET /api/v1/cashea/config` - Consultar parámetros de configuración de Cashea.
- `PUT /api/v1/cashea/config` - Actualizar credenciales y modo de operación.

### 5.3 Autenticación y Turnos de Caja
- `POST /api/v1/auth/login` - Inicio de sesión.
- `POST /api/v1/shifts/open` - Apertura de turno de caja.
- `POST /api/v1/shifts/close` - Cierre ciego de caja (Corte Z con desglose por moneda y método).
- `GET /api/v1/shifts/current` - Estado del turno activo.

### 5.4 Inventario, Proceso de Ajustes (Cabecera/Detalle) y Conteo Físico
- `GET /api/v1/inventory/movement-types` - Listar catálogo de tipos de movimiento de inventario (filtrables por `?naturaleza=ENTRADA` o `?naturaleza=SALIDA`).
- `GET /api/v1/products` - Catálogo con stock, marcas, unidades, categorías y precios duales.
- `POST /api/v1/products` - Crear producto con unidades y galería.
- `PUT /api/v1/products/{id_producto}` - Actualizar producto.
- `GET /api/v1/inventory/adjustments` - Listar documentos de ajuste de inventario con filtros por fecha, estado y tipo.
- `POST /api/v1/inventory/adjustments` - Registrar y aplicar nuevo documento de ajuste con cabecera y múltiples líneas de productos (`id_tipo_movimiento` validado según naturaleza, selección de unidad principal/secundaria con conversión a cantidad base, motivo y documento de referencia).
- `GET /api/v1/inventory/adjustments/{id_ajuste}` - Consultar detalle completo de un ajuste con sus productos y costos.
- `GET /api/v1/inventory/adjustments/{id_ajuste}/pdf` - Descargar acta / comprobante de ajuste de inventario en PDF.
- `GET /api/v1/inventory/{id_producto}/history` - Historial inmutable de movimientos del producto (`movimientos_inventario`).
- `GET /api/v1/inventory/counts` - Listar sesiones de conteo físico y auditoría de inventario.
- `POST /api/v1/inventory/counts` - Iniciar nueva sesión de toma física (general o por marca/categoría).
- `GET /api/v1/inventory/counts/{id_conteo}` - Consultar detalle de sesión con comparación entre stock teórico y físico, discrepancias y valorización.
- `PUT /api/v1/inventory/counts/{id_conteo}` - Guardar/actualizar cantidades físicas contadas en borrador o en proceso (soporte para lectura de código de barras).
- `POST /api/v1/inventory/counts/{id_conteo}/apply` - Aplicar conciliación definitiva: actualiza `stock_actual` en `productos`, genera registros de auditoría en `movimientos_inventario` (tipo `AJUSTE_CONTEO`) y cierra la sesión.
- `POST /api/v1/inventory/counts/{id_conteo}/cancel` - Cancelar sesión de conteo sin modificar existencias.
- `GET /api/v1/inventory/counts/{id_conteo}/export` - Exportar acta de discrepancias y conciliación a PDF o Excel.

### 5.5 POS y Checkout Transaccional (Incluyendo Cashea)
- `GET /api/v1/pos/products` - Búsqueda predictiva optimizada para scanner y teclado.
- `POST /api/v1/pos/checkout` - Checkout transaccional atómico:
  ```json
  {
    "id_cliente": 1,
    "tipo_comprobante": "TICKET",
    "id_moneda": 1,
    "descuento_global": 0.00,
    "items": [
      {
        "id_producto": 10,
        "cantidad": 1,
        "precio_unitario": 100.00,
        "descuento": 0.00
      }
    ],
    "pagos": [
      {
        "id_metodo_pago": 3,
        "id_moneda": 2,
        "monto": 3400.00,
        "tasa_cambio": 85.0000,
        "referencia": "INICIAL-PM-123456"
      },
      {
        "id_metodo_pago": 6,
        "id_moneda": 1,
        "monto": 60.00,
        "tasa_cambio": 1.0000,
        "referencia": "CSH-987654"
      }
    ],
    "cashea": {
      "cedula": "V-18765432",
      "telefono": "0414-1234567",
      "referencia": "CSH-987654",
      "porcentaje_inicial": 40.00,
      "monto_inicial": 40.00,
      "monto_financiado": 60.00,
      "cuotas": 3,
      "monto_cuota": 20.00,
      "codigo_autorizacion": "AUTH-998811"
    }
  }
  ```

### 5.6 Pistas de Auditoría (Audit Trail)
- `GET /api/v1/audits` - Listar registros de auditoría con filtros por rango de fechas, usuario (`id_usuario`), módulo (`modulo`), acción (`accion`) y tabla (`tabla_afectada`).
- `GET /api/v1/audits/{id_auditoria}` - Consultar detalle de un evento de auditoría con visualización del diff de campos (`valores_anteriores` vs. `valores_nuevos`).
- `GET /api/v1/audits/entity/{tabla}/{id_registro}` - Consultar historial completo de modificaciones de una entidad específica (ej. trazabilidad de un producto o de un ajuste).
- `GET /api/v1/audits/export` - Exportar log de auditoría a PDF o Excel para cumplimiento y revisiones de seguridad.

---

## SECCIÓN 6: PLAN DE IMPLEMENTACIÓN POR FASES

```
[Fase 1: Setup Breeze y Base de Datos] ──► [Fase 2: Monedas, Tasas y Caja] ──► [Fase 3: Inventario y Movimientos]
       │                                           │                                      │
       ▼                                           ▼                                      ▼
Laravel 11 + Breeze Vue/Inertia,            CRUD Monedas, Tasas de Cambio,        CRUD Productos, Ajustes (Cab/Det),
Migraciones 23 tablas, Modelos Allman.      Sanctum, Cierre Ciego de Caja.        Conteo Físico y Auditoría Inventario.
-----------------------------------------------------------------------------------------------------------------
       │
       ▼
[Fase 4: POS Multimoneda + Módulo Cashea] ─► [Fase 5: Facturación Interna] ───► [Fase 6: Auditorías y Cierre MVP]
       │                                           │                                      │
       ▼                                           ▼                                      ▼
Atajos Teclado, Carrito Pinia,              Tickets ESC/POS con leyenda Cashea,   Módulo Auditorías (Visor Diff),
Checkout Cashea (Inicial + Cuotas).         Comprobantes PDF/QR, Límites Crédito. Reportes Analytics, Go-Live.
```