# Arquitectura del Sistema de Alquiler de Canchas

## 1) Estilo arquitectonico

- Monolito modular sobre Laravel 12.
- Separacion por dominios: Catalogo, Predios/Canchas, Disponibilidad, Reservas, Pagos, Administracion, Reportes.
- Frontend mixto: Inertia + Vue 3 + TypeScript para pantallas generales y Livewire 4 para UI operativa rapida.

## 2) Capas principales

- Capa de entrada HTTP:
    - Controladores API por perfil en `app/Http/Controllers/Api`.
    - Autenticacion por sesion en `app/Http/Controllers/Auth/SessionAuthController.php`.
- Capa de negocio:
    - `app/Services/CourtAvailabilityService.php`
    - `app/Services/AvailabilitySearchService.php`
    - `app/Services/ReservationService.php`
- Capa de persistencia:
    - Modelos Eloquent en `app/Models`.
    - Migraciones versionadas en `database/migrations`.

## 3) Modelo de datos (resumen)

### Catalogo y ubicacion

- `provinces`
- `cities`
- `sports`
- `services_catalog`

### Gestion de complejos

- `complexes`
- `complex_user_assignments`
- `complex_services`
- `courts`
- `complex_opening_hours`
- `complex_special_dates`
- `court_blocks`
- `court_price_rules`
- `complex_policies`

### Operacion de reservas y pagos

- `reservations`
- `reservation_status_histories`
- `payments`
- `payment_events`

### Reporteria

- `kpi_owner_daily`
- `kpi_global_daily`

### Usuarios

- `users` extendida con `role`, `status`, `phone`, `last_login_at`.

## 4) Reglas de negocio implementadas

- Disponibilidad real por cancha:
    - Verifica estado activo de complejo y cancha.
    - Verifica horario de apertura o excepcion diaria.
    - Verifica bloqueos manuales de cancha.
    - Verifica solapamiento con reservas pendientes/confirmadas.
- Creacion de reserva:
    - Transaccion SQL con lock pesimista.
    - Genera codigo de reserva.
    - Calcula monto total y seña segun politica del complejo.
    - Crea historial de estado y registro de pago inicial.
- Cancelacion de reserva:
    - Respeta ventana limite para cliente.
    - Permite override por AdminCancha/SuperAdmin segun permisos.
    - Registra trazabilidad en historial.

## 5) Seguridad y permisos

- Middleware de rol en `app/Http/Middleware/EnsureUserRole.php`.
- Roles soportados:
    - `cliente`
    - `admin_cancha`
    - `super_admin`
- Rutas separadas por perfil en `routes/api.php`.

## 6) Endpoints principales

### Publico

- `GET /api/availability`

### Cliente

- `POST /auth/register`
- `POST /auth/login`
- `GET /auth/me`
- `POST /auth/logout`
- `GET /api/client/reservations`
- `POST /api/client/reservations`
- `POST /api/client/reservations/{reservation}/cancel`

### AdminCancha

- `GET /api/admin/complexes`
- `POST /api/admin/complexes`
- `PUT /api/admin/complexes/{complex}`
- `PUT /api/admin/complexes/{complex}/opening-hours`
- `PUT /api/admin/complexes/{complex}/policy`
- `GET /api/admin/complexes/{complex}/reservations-grid`
- `GET /api/admin/complexes/{complex}/dashboard`
- `POST /api/admin/complexes/{complex}/courts`
- `PUT /api/admin/courts/{court}`
- `DELETE /api/admin/courts/{court}`

### SuperAdmin

- `GET /api/super-admin/admin-cancha-users`
- `POST /api/super-admin/admin-cancha-users`
- `PUT /api/super-admin/admin-cancha-users/{user}`
- `POST /api/super-admin/admin-cancha-users/{user}/assign-complex`
- `GET /api/super-admin/clients`
- `PUT /api/super-admin/clients/{user}`
- `GET /api/super-admin/dashboard`

## 7) Datos semilla

- Seeder de catalogo: `database/seeders/CatalogSeeder.php`.
- Incluye deportes, servicios y ubicacion base de Tucuman.

## 8) Pruebas automatizadas

- `tests/Feature/AvailabilitySearchTest.php`
- `tests/Feature/ReservationFlowTest.php`

Cubren disponibilidad real, anti doble reserva y politica de cancelacion.
