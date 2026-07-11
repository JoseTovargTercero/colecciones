# AGENTS.md — Contexto de la Aplicación "Control de Deudas / Colecciones"

## 1. DESCRIPCIÓN GENERAL
- **Nombre:** Control de Deudas / Colecciones
- **Tipo:** ERP de cobranzas con gestión de suscripciones, vendedores, colecciones y premios
- **URL base:** `http://localhost/colecciones/` (constante `BASE_URL`)
- **PHP:** 7.4.* con polyfills para funciones PHP 8
- **Frontend:** Bootstrap 5 / Sneat admin theme, jQuery, DataTables, SweetAlert2
- **Base de datos:** MariaDB vía MySQLi
- **Arquitectura:** MVC casero (Front Controller + Router propio)
- **Autoload:** Composer PSR-4 (`App\` mapeado a raíz)
- **Autor:** Jesús Zapata

## 2. ESTRUCTURA DE DIRECTORIOS
```
├── index.php                  ← Front Controller (entry point único)
├── Router.php                 ← Router propio (App\Router)
├── config/                    ← Database.php (singleton MySQLi), ClientEnvironmentInfo.php, TimezoneManager.php, geolite.mmdb
├── controllers/               ← 27 controladores
├── models/                    ← 25 modelos
├── views/                     ← auth/, layouts/, suscripcion/, modules/, partials/, admin/, 404.php
├── middlewares/               ← 6 middlewares
├── helpers/                   ← helpers.php, login_helpers.php, mailHelper.php, push.php, etc.
├── core/                      ← ViewRenderer.php
├── public/assets/             ← CSS, JS, imágenes, Sneat template
├── uploads/                   ← articulos/, colecciones/, comprobantes/, premios/
├── admin/                     ← Panel admin para aprobar pagos de suscripción
├── implementar/               ← SQL scripts (suscripciones.sql, password_resets.sql, menu.sql, users_permisos.sql)
├── cron/                      ← Scripts de cron (actualizar_estatus_cuotas.php, cron_reproduccion.php, registrar_consumo_automatico.php)
├── downloads/                 ← app.txt
├── landing_page/              ← Landing page de ERP SISUPP (sistema ganadero)
├── md/                        ← Documentación markdown del sistema ganadero (no relevante para colecciones)
├── .env_colecciones*          ← NO existe en repo (gitignored). Debe crearse.
├── env_colecciones*           ← Fallback sin punto. NO existe en repo.
├── .env_erpg*                 ← Usado por backend/. NO existe en repo.
└── vendor/                    ← Dependencias Composer
```

## 3. SESIÓN
Se inicia en `index.php:45-47` con `session_start()`.

### Variables de sesión clave:
- `$_SESSION['user_id']` — UUID del usuario logueado
- `$_SESSION['logged_in']` — bool
- `$_SESSION['nombre']` — Nombre completo
- `$_SESSION['codigo']` — Código de usuario (login legacy)
- `$_SESSION['nivel']` — 0=admin, 1=usuario
- `$_SESSION['permisos']` — array de URLs permitidas
- `$_SESSION['user_type']` — 'user' o 'administrator'
- `$_SESSION['tipo']` — 'vendedor' o 'gerente'
- `$_SESSION['session_id']` — UUID de la sesión auditada
- `$_SESSION['suscripcion']` — ['tipo','fecha_fin','estatus']
- `$_SESSION['bcv_valor']` — Tasa BCV
- `$_SESSION['timezone']` — Zona horaria (default: 'America/Caracas')
- `$_SESSION['login_attempts_' . md5($email)]` — Rate limiting (3 intentos → 60s lockout)

## 4. ROUTING
- **Archivo:** `Router.php` — regex-based, soporta `{param}`, grupos con prefix/middleware
- **Métodos:** `get()`, `post()`, `put()`, `delete()`
- **Definición de rutas en:** `index.php:118-279`
- **Formato de registro:**
  ```php
  $router->get('/ruta', ['vista' => 'ruta']);  // Para vistas
  $router->post('/api/ruta', ['controlador' => 'Controlador::class', 'accion' => 'metodo']);  // Para APIs
  $router->group(['prefix' => '/api', 'middleware' => LoginSuscripcionMiddleware::class], function($router) { ... });
  ```
- **Middleware disponibles:**
  - `SessionRedirectMiddleware` — Si ya logueado, redirige a /perfil
  - `LoginRequiredMiddleware` — Si no logueado, redirige a /login
  - `SuscripcionMiddleware` — Verifica suscripción activa, redirige si vencida/pendiente
  - `LoginSuscripcionMiddleware` — Combinación de login + suscripción
  - `AuthMiddleware` — Verifica autenticación + nivel de permiso

## 5. AUTENTICACIÓN
- **Login principal:** `POST /api/system_users/login` → `SystemUserController::login()` → `SystemUserModel::loginBasico()`
  - Acepta JSON: `{ email, password, device_id?, is_mobile? }`
  - Verifica bcrypt, rate limiting (3 fallos → 60s lockout), registra auditoría en `session_management`
- **Login app:** `SystemUserController::loginApp()` — similar, pero actualiza `dispositivo_token`
- **Login legacy:** `POST /api/login` → `AuthController::login()` — contra tabla `centros` (sin hash)
- **Logout:** `GET /api/logout` → `session_unset()` + `session_destroy()` + redirect
- **Recuperación:** `POST /api/recovery/verify-email` → envía email con token (10min); `POST /api/recovery/update-password`
- **Admin panel suscripciones:** `admin/index.php` — credenciales hardcodeadas admin/admin

## 6. BASE DE DATOS

### Configuración
- **Clase:** `config/Database.php` — Singleton, MySQLi
- **Conexión:** Lee de `.env_colecciones` o `env_colecciones` en `APP_ROOT` o `APP_ROOT/../../`
- **Charset:** `utf8mb4`

### Variables de entorno (.env_colecciones)
```
DB_HOST=localhost
DB_NAME=colecciones
DB_USER=root
DB_PASS=
APP_URL=http://localhost/colecciones
FCM_PROJECT_ID=sissup-cb2db
MAIL_HOST=localhost
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_PORT=465
MAIL_FROM=noreply@example.com
MAIL_FROM_NAME=SISSUP Soporte
```

### Tablas principales

| Tabla | PK | Descripción |
|-------|----|-------------|
| `system_users` | CHAR(36) UUID | Usuarios del sistema (nombre, email, telefono, contrasena(bcrypt), nivel, estado, dispositivo_token, tipo['vendedor'/'gerente'], created_at/by, updated_at/by, deleted_at/by) |
| `session_management` | CHAR(36) UUID | Auditoría de sesiones (user_id, user_name, login/logout_time, login_success, failure_reason, session_status, ip_address, geolocalización, os, browser, device_type, token) |
| `menu` | CHAR(36) UUID | Items del menú de navegación (categoria, nombre, url, icono, user_level, orden) |
| `menu_categorias` | - | Categorías de menú para ordenamiento |
| `users_permisos` | CHAR(36) UUID | Permisos usuario→menu (user_id, menu_id) |
| `configuracion_planes` | INT | Planes de suscripción (id:1=Plan Estandar, precio_mensual:25.00, precio_anual:260.00, moneda:USD) |
| `suscripciones` | INT AUTO_INCREMENT | Suscripciones (usuario_id, plan_id, tipo_pago['trial'/'mensual'/'anual'], fecha_inicio, fecha_fin, estatus['activa'/'vencida'/'cancelada'/'pendiente']) |
| `historial_pagos` | INT AUTO_INCREMENT | Pagos de suscripción (suscripcion_id, monto_pagado, fecha_pago, referencia_pago, created_at) |
| `password_resets` | VARCHAR(255) email PK | Tokens de recuperación (email, token, created_at) |
| `temporadas` | VARCHAR(36) UUID | Temporadas/periodos (nombre, fecha_inicio, fecha_fin, empresa_id, usuario_id) |
| `empresas` | - | Empresas |
| `gerencias` | - | Gerencias/oficinas |
| `colecciones_combos` | - | Colecciones/combos (empresa_id, nombre, foto, precio_base, precio_venta_vendedor, ganancia_vendedor, tipo['coleccion'/'combo'], usuario_id) |
| `articulos` | - | Artículos/items |
| `premios` | - | Premios |
| `preferencias_premios` | - | Preferencias de premios de vendedores |
| `asignaciones` | - | Asignaciones (vendedor→colecciones) |
| `articulo_asignacion` | - | Asignaciones de artículos |
| `control_pagos` | - | Control de pagos |
| `carga_pagos` | - | Carga de pagos |
| `vendedores` | - | Vendedores/clientes |
| `alertas` | - | Alertas del sistema |
| `notifications` | - | Notificaciones (template_key, template_params JSON, route, module, etc.) |
| `tutorial` | - | Estado del tutorial por usuario |
| `centros` | - | Tabla legacy para login antiguo |

### Auditoría automática
Todas las tablas con `created_at`/`updated_at` usan `nowWithAudit()` que:
1. Crea `ClientEnvironmentInfo` para capturar IP, OS, browser, geolocalización
2. Ejecuta `applyAuditContext()` seteando variables MySQL (`@user_id`, `@client_ip`, etc.)
3. Aplica timezone vía `TimezoneManager`

## 7. FLUJO DE SUSCRIPCIONES
1. **Registro** → se crea usuario + trial 7 días automático (`SuscripcionModel::crearTrial()` en `SystemUserModel::crear()`)
2. **Middleware** `SuscripcionMiddleware` verifica `$_SESSION['suscripcion']['estatus']` en cada ruta protegida
3. Si trial vencido → redirect a `/suscripcion/vencida`
4. Si pago pendiente → redirect a `/suscripcion/pendiente`
5. Pagos los aprueba admin manualmente en `/admin/index.php`
6. Usuarios `vendedor` ven hasta 5 colecciones; `gerente` hasta 80

## 8. DIFERENCIAS VENDEDOR vs GERENTE
- `vendedor`: vende unidades de colecciones, textos reemplazados ("Vendedor" → "Cliente" via `vendedor_replacements.json`), dashboard propio `/dashboard-vendedor`, menú restringido
- `gerente`: vende colecciones completas, acceso completo al menú

## 9. APIs ENDPOINTS CLAVE
- `POST /api/system_users/login` — Login principal (JSON)
- `POST /api/system_users` — CRUD usuarios
- `POST /api/recovery/verify-email` — Recuperar contraseña paso 1
- `POST /api/recovery/update-password` — Recuperar contraseña paso 2
- `*/api/empresas*` — CRUD empresas
- `*/api/temporadas*` — CRUD temporadas
- `*/api/gerencias*` — CRUD gerencias
- `*/api/colecciones*` — CRUD colecciones
- `*/api/articulos*` — CRUD artículos
- `*/api/premios*` — CRUD premios
- `*/api/vendedores*` — CRUD vendedores + buscarPorCedula + solicitarPago
- `*/api/asignaciones*` — CRUD asignaciones + cuotas
- `*/api/asignaciones-articulos*` — CRUD asignaciones artículos + cuotas + detalle
- `*/api/control-pagos*` — Control pagos + historial + premioInfo + solicitarPremio
- `*/api/cargar-pago*` — Cargar pago + cuotas + deuda
- `*/api/preferencias-premios*` — Preferencias + historial + pagosTiempo + asignarPremios
- `*/api/dashboard/kpis*` — KPIs dashboard
- `*/api/alertas*` — CRUD alertas
- `*/api/notifications*` — CRUD notificaciones + flags + push
- `*/api/tutorial/state*` — Estado tutorial
- `*/api/bcv/refresh*` — Refrescar tasa BCV
- `*/api/cron/actualizar-estatus*` — Cron (sin middleware)

## 10. CONSTANTES GLOBALES (definidas en index.php)
- `APP_ROOT` → `__DIR__ . '/'` (path archivos)
- `BASE_URL` → `http(s)://host/path/` (path URLs)

## 11. CONVENCIONES DE CÓDIGO
- PHP 7.4, sin tipado estricto en mayoría de archivos
- Nombres de métodos en camelCase
- Vistas en `views/` con subdirectorios por módulo
- Layout principal: `views/layouts/main.php`
- Partials: `views/partials/head.php`, `sidebar.php`, `topbar.php`, `footer.php`, `footer_scripts.php`
- Los controladores no extienden una clase base
- Cada modelo es una clase independiente (sin herencia)
- Las respuestas API son JSON con `['value' => bool, 'message' => string, 'data' => mixed]`
- Para exportar datos a vistas se usa `ViewRenderer::render($vista, $data)` donde `$data` es un array que se extrae como variables

## 12. NOTAS IMPORTANTES
- No hay `.env_colecciones` en el repo — hay que crearlo para que funcione la DB
- No hay migraciones automáticas — los SQL están en `implementar/`
- Para crear tablas faltantes, ejecutar scripts manualmente desde `implementar/`
- El proyecto no usa framework PHP moderno — es totalmente artesanal
- Los PDFs se generan con TCPDF
- Los emails se envían con PHPMailer
- Las notificaciones push usan Firebase Cloud Messaging v1 API
- BCV rate se obtiene de `https://iseller-tiendas.com/inventario/configurar/bcv_api.php`
