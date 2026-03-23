# Cancha Alquiler

Cancha Alquiler es un sistema integral de reservas y gestión de complejos deportivos moderno y eficiente. Permite a los usuarios buscar y reservar canchas, mientras que los administradores de los complejos y los superadministradores pueden gestionar completamente las instalaciones, horarios, precios y estadísticas (KPIs).

## Características Principales

### 🔍 Buscador Inteligente (Para Clientes)

- Búsqueda de disponibilidad en tiempo real por **Deporte**, **Provincia**, **Ciudad**, **Fecha** y **Hora**.
- Filtra y muestra únicamente los horarios y canchas que realmente están disponibles en el rango elegido.
- Perfil detallado de complejos que incluye fotos, ubicación en mapa, servicios, políticas de cancelación e información de contacto.

### 👥 Sistema Multi-Rol

El sistema cuenta con 3 roles principales, cada uno con su panel de control (Dashboard) específico:

1. **Cliente (`cliente`)**:
    - Búsqueda y reserva de canchas.
    - Panel para gestionar y visualizar sus próximas reservas y su historial.
    - Tablero de equipos (Team Board) para buscar compañeros o postularse a equipos.
2. **Administrador de Cancha (`admin_cancha`)**:
    - Asignado a uno o más complejos deportivos.
    - **Gestión de Complejo**: Horarios de apertura regulares, fechas especiales (feriados/cierres parciales), servicios y políticas.
    - **Gestión de Canchas**: Creación de canchas por deporte, configuración de capacidad y duración de los turnos.
    - **Reglas de Precios Dinámicos (`CourtPriceRule`)**: Precios que varían según el horario, día de la semana o fechas concretas (ej. la tarifa nocturna o de fin de semana).
    - **Bloqueos de Cancha (`CourtBlock`)**: Posibilidad de bloquear una cancha temporalmente (por mantenimiento, clases, etc.).
    - **Gestión de Torneos (`ComplexTournament`)**: Creación de torneos y gestión de los equipos inscritos (`TournamentTeam`).
    - **Pagos y Reservas**: Control de las reservas hechas por los clientes, historial de estados y registros de pagos (`PaymentHistory`, `PaymentEvent`).
    - **KPIs (`KpiOwnerDaily`)**: Estadísticas de ingresos, canchas más alquiladas y rendimiento diario/mensual del complejo.
3. **Super Administrador (`super_admin`)**:
    - Control global del sistema.
    - Gestión de Provincias, Ciudades y listado base de Deportes.
    - Administración de todos los usuarios, roles y complejos (`ComplexUserAssignment`).
    - **Global KPIs (`KpiGlobalDaily`)**: Panel con reportes globales del rendimiento de toda la plataforma.

### 🎨 Diseño y UI (Interfaz de Usuario)

- Desarrollado usando **Vue 3** y **Inertia.js** (Single Page Application - SPA sin recargas de página).
- Estilizado con **Tailwind CSS v4** para ofrecer un diseño moderno, limpio e intuitivo.
- **Soporte de Tema Claro y Oscuro**: Implementación dinámica que memoriza la preferencia visual (`localStorage`) y reacciona de manera general ofreciendo accesibilidad visual según el gusto del usuario. (Modo Claro por defecto).
- Alertas dinámicas y formularios interactivos para una reserva fluida.

## Pila Tecnológica (Tech Stack)

### Backend

- **Framework:** Laravel 11.x (PHP 8.2+)
- **Base de Datos:** MySQL / MariaDB (Manejo mediante Laravel Eloquent ORM y Migraciones).
- **Sistema de Rutas:** Integración Laravel + Inertia.js.

### Frontend

- **Framework:** Vue 3 (Composition API / `<script setup>`).
- **Navegación:** Inertia.js v2.
- **Estilos:** Tailwind CSS v4.
- **Compilador:** Vite.
- **Tipado:** TypeScript para una mejor consistencia y prevención de errores en Vue de forma estricta.

## Instalación Local

1. Clona este repositorio o descarga el código.
2. Instala las dependencias de PHP usando Composer:
    ```bash
    composer install
    ```
3. Instala las dependencias de Node.js:
    ```bash
    npm install
    ```
4. Copia el archivo `.env.example` y renómbralo a `.env`:
    ```bash
    cp .env.example .env
    ```
5. Genera la llave de configuración de Laravel:
    ```bash
    php artisan key:generate
    ```
6. Configura tu base de datos en el archivo `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
7. Ejecuta las migraciones y seeders (para poblar ciudades, provincias, roles y catálogo default):
    ```bash
    php artisan migrate --seed
    ```
8. Inicia el servidor de desarrollo de Vite (Frontend) en una terminal:
    ```bash
    npm run dev
    ```
9. En otra terminal, levanta el entorno de Laravel:
    ```bash
    php artisan serve
    ```
10. Abre la aplicación en tu navegador: `http://localhost:8000`.

## Resumen de cambios recientes (UI / Design System)

Se integró un pequeño design system y se aplicaron mejoras visuales en varias vistas principales para unificar la apariencia y mejorar la accesibilidad:

- Archivos clave añadidos/actualizados:
    - `resources/css/interface-design.css` — variables de diseño (paleta, espacios, sombras), clases helper: `.btn-primary`, `.btn-secondary`, `.form-field`, `.card`, `.card--muted` y reglas de focus accesible.
    - `vite.config.js` — ajuste de `server.hmr.host` a `localhost` para evitar problemas de HMR/ERR_ADDRESS_INVALID en Windows.
    - Varias vistas modificadas para usar las nuevas helpers `card`/`card--muted` y `form-field`:
        - `resources/js/Pages/AdminCancha/Dashboard.vue`
        - `resources/js/Pages/SuperAdmin/Dashboard.vue`
        - `resources/js/Pages/Complex/Show.vue`
        - `resources/js/Pages/Home.vue`
        - `resources/js/Pages/Client/Dashboard.vue`
        - `resources/js/Pages/Auth/Login.vue`
        - `resources/js/Pages/Auth/Register.vue`
        - `resources/views/livewire-demo.blade.php`

- Objetivos del cambio:
    - Unificar wrappers visuales en `.card` para consistencia.
    - Mejorar contraste y foco (outline) para accesibilidad.
    - Normalizar botones y formularios con helpers reutilizables.

## Cómo probar visualmente (rápido)

1. Instala dependencias (si aún no lo hiciste):

```bash
npm install
composer install
```

2. Inicia Vite (dev) y Laravel en terminales separadas:

```bash
npm run dev -- --host
php artisan serve --host=127.0.0.1 --port=8000
```

3. Abre la app en el navegador: `http://127.0.0.1:8000` y la consola de Vite en `http://localhost:5173` (si quieres ver HMR). Haz una recarga completa (Ctrl+F5) si no ves los cambios.

4. Si ves errores tipo `ERR_ADDRESS_INVALID` en la consola del navegador, asegúrate de que `vite.config.js` tiene `server.hmr.host` configurado a `localhost` y que el proceso de Vite está corriendo.

## Archivos modificados (rápido)

Listado resumido de archivos que se modificaron en esta sesión para aplicar el design system:

- `resources/css/interface-design.css`
- `vite.config.js`
- `resources/js/Pages/AdminCancha/Dashboard.vue`
- `resources/js/Pages/SuperAdmin/Dashboard.vue`
- `resources/js/Pages/Complex/Show.vue`
- `resources/js/Pages/Home.vue`
- `resources/js/Pages/Client/Dashboard.vue`
- `resources/js/Pages/Auth/Login.vue`
- `resources/js/Pages/Auth/Register.vue`
- `resources/views/livewire-demo.blade.php`

Si querés, puedo preparar un commit con un mensaje claro y agrupar los cambios, o generar un diff con todos los cambios aplicados para revisión.

## Scripts Útiles

- `npm run dev`: Inicia el servidor de desarrollo en caliente (HMR) mediante Vite.
- `npm run build`: Compila los assets de Tailwind y Vue 3 para entorno de producción.
- `npm run type-check`: Ejecuta `vue-tsc` para comprobar rápidamente la tipificación del código TypeScript en los componentes.

---

_Hecho para centralizar y mejorar la experiencia de reserva de espacios deportivos._
