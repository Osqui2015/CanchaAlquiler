# Cancha Alquiler

Cancha Alquiler es un sistema de administración y reservas de complejos deportivos. Soporta búsqueda y reserva por deporte/ciudad/fecha, gestión de complejos, canchas, turnos fijos, torneos, rankings por deporte/modalidad y reportes para administradores.

## Características principales

- Búsqueda de disponibilidad por deporte, provincia, ciudad, fecha y hora.
- Gestión de complejos y canchas (horarios, políticas, precios dinámicos, bloqueos).
- Sistema de roles: `cliente`, `admin_cancha`, `super_admin` con dashboards específicos.
- Módulos: reservas (rápidas y fijas), torneos, tablero de equipos, rankings por deporte/modalidad y KPIs.

## Pila tecnológica

- Backend: Laravel 11 (PHP 8.2+), MySQL/MariaDB
- Frontend: Vue 3 + Inertia, TypeScript, Vite, Tailwind CSS

## Instalación local (rápida)

1. Clonar repo.
2. Instalar dependencias PHP y JS:

```bash
composer install
npm install
```

3. Copiar `.env.example` a `.env` y configurar la DB.
4. Generar key:

```bash
php artisan key:generate
```

5. Ejecutar migraciones y seeders (población básica):

```bash
php artisan migrate --seed
```

6. Iniciar desarrollo:

```bash
npm run dev
php artisan serve
```

7. Abrir `http://127.0.0.1:8000`.

## Seeders y datos de ejemplo

Se añadió un seeder de ejemplo para jugadores de pádel:

- `database/seeders/PadelPlayersSeeder.php` — crea ~8 usuarios de prueba (role `cliente`) y sus entradas en `user_rankings` con `rankid` y `points`.

Ejecutar sólo ese seeder:

```bash
php artisan db:seed --class=PadelPlayersSeeder
```

Si añadís seeders o clases nuevas y no son encontradas por PHP, ejecutá:

```bash
composer dump-autoload
```

Notas:

- Las contraseñas generadas por el seeder son `password` (hashed por Laravel). Cambialas en entornos reales.
- Si el seeder falla por columnas faltantes en `user_rankings`, ejecutá `php artisan migrate`.

## Endpoints útiles (panel admin)

- `GET /panel/admin-cancha` — Panel principal (Inertia page)
- `GET /panel/admin-cancha/rankings?deporte=<id|slug>&modalidad=<tipo>` — Rankings filtrados (protegido por `role:admin_cancha`).
- Varias rutas CRUD para complejos, canchas, reservas y clientes bajo `/panel/admin-cancha/...` (ver `routes/web.php`).

El controlador para el endpoint de ranking es `app/Http/Controllers/Api/RankingController.php`.

## Frontend: verificación y desarrollo

- Levantar dev server de Vite:

```bash
npm run dev
```

- Comprobación de tipos (vue-tsc):

```bash
npm run type-check
```

Si `vue-tsc` informa errores en componentes grandes (ej. `Dashboard.vue`), se han extraído partials para facilitar la corrección:

- `resources/js/Pages/AdminCancha/parts/ReservationsPart.vue`
- `resources/js/Pages/AdminCancha/parts/ClientsPart.vue`
- `resources/js/Pages/AdminCancha/parts/ModalidadSelector.vue`
- `resources/js/Pages/AdminCancha/parts/RankingByModalidad.vue`

## Scripts útiles

```bash
# Migraciones + seeders
php artisan migrate --seed

# Ejecutar sólo el seeder de padel
php artisan db:seed --class=PadelPlayersSeeder

# Regenerar autoload
composer dump-autoload

# Verificación de tipos
npm run type-check
```

## Troubleshooting

- Si el seeder falla con error SQL sobre columnas, revisá `database/migrations` y asegura que las migraciones relacionadas (p. ej. `create_user_rankings_table`) están aplicadas.
- Problemas con HMR en Windows: comprobar `vite.config.js` y `server.hmr.host`.

---

Si querés, hago cualquiera de las siguientes tareas ahora (indicá la letra):

A) Hacer commit con este `README.md` actualizado.
B) Añadir `README-DEV.md` con pasos extendidos para Windows (PowerShell) y Docker.
C) Generar `padel_players.json` con los usuarios creados por el seeder para uso front-end.
