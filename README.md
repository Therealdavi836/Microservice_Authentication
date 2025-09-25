# Microservice Authentication – Concesionario de Vehículos

Servicio de autenticación y gestión básica de identidades para un ecosistema modular (microservicios) de un aplicativo web de un concesionario de vehículos. Provee registro de usuarios, inicio/cierre de sesión mediante tokens personales (Laravel Sanctum), asignación automática de rol inicial, protección por middleware y soporte para pruebas de carga (Locust). Está diseñado para operar de forma independiente y ser consumido por otros servicios (inventario, catálogo, ventas, analítica, etc.).

---

## Tabla de Contenido

1. Propósito y Alcance  
2. Arquitectura y Flujo de Autenticación  
3. Roles Disponibles  
4. Endpoints API (detalle completo)  
5. Especificaciones de Seguridad  
6. Estructura de Carpetas Relevante  
7. Modelos y Relaciones  
8. Migraciones y Seeders  
9. Configuración y Puesta en Marcha (Local / Docker opcional)  
10. Variables de Entorno (.env) – Checklist  
11. Ejemplos de Uso (cURL & JSON)  
12. Pruebas (Unitarias / Feature) & Cobertura  
13. Pruebas de Carga con Locust  
14. Middleware Personalizado (FilterIP)  
15. Buenas Prácticas y Recomendaciones Operativas  
16. Estrategia de Escalabilidad y Observabilidad (futuro)  
17. Roadmap Sugerido  
18. Troubleshooting

---

## 1. Propósito y Alcance

Este microservicio centraliza la autenticación de usuarios del concesionario. Gestiona:

- Registro de nuevos usuarios (rol inicial: customer).
- Generación y revocación de tokens de acceso (Bearer) vía Laravel Sanctum.
- Inicio y cierre de sesión seguro (stateless para API).
- Control básico de acceso mediante roles (extensible).
- Puntos de prueba aislados para benchmarking de rendimiento usando Locust y un middleware de filtrado por IP.

No incluye (aún): verificación de correo, recuperación de contraseña, refresco de tokens, ni autorización granular por permisos; se dejan en el roadmap.

## 2. Arquitectura y Flujo de Autenticación

Resumen del flujo principal:

1. Cliente realiza POST /api/register → se crea usuario con rol customer y se emite token personal (personal access token).
2. Cliente realiza POST /api/login con credenciales → se valida y emite nuevo token.
3. Cliente usa el token en encabezado `Authorization: Bearer <token>` para acceder a endpoints protegidos (ej. /api/logout u otros futuros).
4. Al cerrar sesión (POST /api/logout) se eliminan todos los tokens activos del usuario.

Características:

- Tokens emitidos con Sanctum: se almacenan en tabla `personal_access_tokens`.
- Diseño stateless para la API (no depende de sesión de servidor en endpoints de autenticación básica).
- Middleware `filter.ip` para aislar endpoints de stress test: /api/test/*.

## 3. Roles Disponibles

Seed inicial (ver `RoleSeeder`):

| name     | label             | description                                      |
|----------|-------------------|--------------------------------------------------|
| admin    | Administrator     | Acceso completo al sistema                       |
| seller   | Seller            | Publica y gestiona vehículos                     |
| customer | Customer          | Explora vehículos y gestiona compras             |
| support  | Technical Support | Gestiona soporte y consultas                     |

Asignación inicial: todo usuario registrado vía /register recibe el rol `customer`. Cambios de rol se realizarán a través de un panel administrativo o endpoint futuro restringido a `admin`.

## 4. Endpoints API

Base: `/api`

| Método | Ruta             | Auth | Descripción | Middleware Extra |
|--------|------------------|------|-------------|------------------|
| POST   | /register        | No   | Registro de nuevo usuario y emisión de token | - |
| POST   | /login           | No   | Inicio de sesión y emisión de token          | - |
| POST   | /logout          | Sí   | Revocar tokens activos del usuario           | auth:sanctum |
| POST   | /test/register   | No   | Igual a /register solo para pruebas de carga | filter.ip |
| POST   | /test/login      | No   | Igual a /login para pruebas de carga         | filter.ip |

Respuestas estándar:

- 200/201: Éxito (JSON con token u objeto).
- 401: Credenciales inválidas.
- 403: Rechazo de IP (middleware).
- 422: Error de validación.

## 5. Especificaciones de Seguridad

- Framework: Laravel 10 + Sanctum.
- Hash de contraseñas: `bcrypt` (Hash::make) configurable en `config/hashing.php`.
- Token Bearer: enviada en `Authorization: Bearer <token>`.
- Revocación: `logout` elimina todos los tokens (`$request->user()->tokens()->delete()`).
- Protección de stress tests: `FilterIP` valida lista blanca (127.0.0.1, ::1). Extensible para CI/CD o IP internas.
- Anti-Enumeración: Mensajes genéricos en credenciales inválidas. (Posible mejora: retrasos progresivos o rate limiting granular por login.)
- Rate limiting: Perfil API incluye throttle (ver `Kernel.php` → grupo `api`). Ajustable en `RouteServiceProvider` / `config/ratelimit` (no custom aún).
- CSRF: No aplica a endpoints API stateless (Santcum maneja estado si se mezcla con front SPA; aquí se asume flujo token clásico).

## 6. Estructura de Carpetas Relevante

```text
app/Models/User.php
app/Models/Role.php
app/Http/Controllers/AuthController.php
app/Http/Middleware/FilterIP.php
routes/api.php
database/migrations/* (roles, users, tokens)
database/seeders/RoleSeeder.php
Locust_Test/locustfile.py
```

## 7. Modelos y Relaciones

`User` pertenece a un `Role` (FK `role_id`, nullable para flexibilidad).  
`Role` tiene muchos `User`.

## 8. Migraciones y Seeders

Migraciones claves:

- `create_roles_table`
- `add_role_id_to_users_table` (agrega FK a users)
- `create_personal_access_tokens_table` (por Sanctum – incluida por vendor/migraciones base)

Seeders:

- `RoleSeeder` → Inserta roles base.
- `DatabaseSeeder` (puede invocar RoleSeeder si se añade la llamada).

Comandos:

```bash
php artisan migrate --seed          # Ejecuta migraciones y seeders configurados
php artisan db:seed --class=RoleSeeder
```

## 9. Configuración y Puesta en Marcha

Requisitos:

- PHP >= 8.1
- Composer
- Servidor de base de datos (MySQL/MariaDB recomendado) o SQLite para desarrollo.
- Extensiones PHP típicas habilitadas (mbstring, openssl, pdo, tokenizer, xml, ctype, json).

Pasos Local (sin Docker):

```bash
git clone <REPO_URL>
cd Microservice_Authentication
cp .env.example .env
composer install
php artisan key:generate
# Configurar variables DB en .env
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```
La API quedará accesible en: `http://localhost:8000/api`.

Opcional – SQLite rápido:

```bash
touch database/database.sqlite
echo DB_CONNECTION=sqlite >> .env
php artisan migrate --seed
```

## 10. Variables de Entorno (.env) – Checklist

| Clave | Ejemplo | Notas |
|-------|---------|-------|
| APP_NAME | "ConcesionarioAuth" | Nombre lógico |
| APP_ENV | local | prod/staging/local |
| APP_KEY | (auto) | Generado con artisan key:generate |
| APP_DEBUG | true | Desactivar en producción |
| APP_URL | <http://localhost> | Base para enlaces |
| LOG_CHANNEL | stack | Logging configurable |
| DB_CONNECTION | mysql | mysql / pgsql / sqlite |
| DB_HOST | 127.0.0.1 | |
| DB_PORT | 3306 | |
| DB_DATABASE | concesionario_auth | |
| DB_USERNAME | root | |
| DB_PASSWORD | secret | |
| SANCTUM_STATEFUL_DOMAINS | localhost:5173 | Para SPA si aplica |
| SESSION_DOMAIN | localhost | (si se usa Sanctum modo SPA) |
| FRONTEND_URL | <http://localhost:5173> | Integración front |

## 11. Ejemplos de Uso (cURL)

Registro:

```bash
curl -X POST http://localhost:8000/api/register \\
  -H "Content-Type: application/json" \\
  -d '{"name":"Juan Perez","email":"juan.perez@example.com","password":"password123"}'
```
Respuesta:
```json
{ "access_token": "<token>", "token_type": "Bearer" }
```

Login:
```bash
curl -X POST http://localhost:8000/api/login \\
  -H "Content-Type: application/json" \\
  -d '{"email":"juan.perez@example.com","password":"password123"}'
```
Logout:
```bash
curl -X POST http://localhost:8000/api/logout \\
  -H "Authorization: Bearer <token>"
```

Endpoints de prueba (solo IP autorizada):
```bash
curl -X POST http://localhost:8000/api/test/login \\
  -H "Content-Type: application/json" \\
  -d '{"email":"user_test@example.com","password":"password123"}'
```

## 12. Pruebas (PHPUnit)

Ejecutar:

```bash
php artisan test
```

Agregar futuros tests para:

- Respuestas de validación 422.
- Acceso restringido con tokens inválidos.
- Asignación automática de rol customer.

## 13. Pruebas de Carga con Locust

Archivo: `Locust_Test/locustfile.py`

Requisitos: Python 3.11+ y Locust instalado.

```bash
pip install locust
cd Locust_Test
locust -H http://localhost:8000
```

Abrir interfaz: <http://localhost:8089>  
Escenarios incluidos: registro masivo, login repetido, logout condicionado.  
Sugerencia: usar dataset controlado de usuarios pre-creados para pruebas de login evitando saturar base con registros infinitos.

## 14. Middleware Personalizado – FilterIP

Archivo: `app/Http/Middleware/FilterIP.php`

Función: Autoriza solo IPs en lista blanca para endpoints `/api/test/*`. Ideal para no exponer rutas de carga a internet pública.

Extensión sencilla: mover IPs a `.env` (ej: `LOAD_TEST_IPS=127.0.0.1,10.0.0.5`) y parsearlas.

## 15. Buenas Prácticas y Recomendaciones

- Rotar tokens: permitir expiración configurable (extensión futura vía columnas `expires_at`).
- Registrar auditoría: Logins y revocaciones (Monolog / futura tabla audit_logs).
- Monitoreo: Integrar métricas (Prometheus + exporter Laravel) y alertar por picos anómalos.
- Evitar fuga de errores: Mantener mensajes de error genéricos en login.
- Protección extra: Agregar captcha tras N intentos fallidos.

## 16. Escalabilidad y Observabilidad (Diseño Futuro)

- Stateless horizontal scaling detrás de un balanceador.
- Cacheo de roles/permisos en Redis.
- Distribución de eventos (UserRegistered) vía cola para otros microservicios.
- Integración OpenTelemetry para trazas distribuidas.

## 17. Roadmap Sugerido

- [ ] Endpoint cambio de rol (solo admin).
- [ ] Verificación de correo y reenvío de token.
- [ ] Recuperación / restablecimiento de contraseña.
- [ ] Bloqueo temporal tras intentos fallidos (rate limit adaptativo).
- [ ] Permisos granulares (tabla permissions + pivot role_permission).
- [ ] Expiración y refresco de tokens.
- [ ] Multi-factor (TOTP / Email OTP / SMS).
- [ ] Webhooks para eventos de autenticación.

## 18. Troubleshooting

| Problema | Causa Común | Solución |
|----------|-------------|----------|
| 419 / CSRF token mismatch | Uso indebido de cookies en API pura | Omite CSRF en API stateless; asegúrate de usar Authorization Bearer |
| 401 credenciales inválidas | Email/contraseña erróneos | Verifica hash y migraciones ejecutadas |
| 403 en /api/test/* | IP no incluida | Añade IP en middleware o ajusta lista blanca |
| 500 al registrar | Migraciones/seeders faltan | Ejecuta `php artisan migrate --seed` |
| Token no funciona | Header mal formado | Usa `Authorization: Bearer <token>` |

### Licencia

MIT (heredada de plantilla Laravel). Ajustar si el proyecto corporativo requiere otra.

### Contacto / Mantenimiento

Equipo Backend – Microservicios Concesionario. Añadir emails corporativos o canal interno.

---
Siéntete libre de extender este README con métricas, diagramas de secuencia, o instrucciones Docker Compose cuando se integre con otros servicios.

