# Contexto de Desarrollo — Cancha Alquiler

## Objetivo General

Desarrollar un panel integral para el cliente en `/panel/cliente` que incluya:

- Edición de perfil y avatar
- Historial de alquileres (activas, futuras, completadas, canceladas)
- Sistema de ranking y puntos por deporte
- Gestión de amigos, parejas y equipos
- Complejos favoritos
- Recomendaciones extra: nivel de juego, notificaciones, split payment, estadísticas rápidas

---

## Estado Actual

- El panel ya muestra reservas (regulares y recurrentes) sin errores de backend ni frontend.
- Faltan datos completos y todas las secciones sociales, historial, edición de perfil, ranking, amigos, favoritos, etc.

---

## Requerimientos Funcionales (Criterios de Aceptación)

1. **Edición de Perfil y Avatar**
    - Formulario para editar datos básicos del perfil.
    - Subida de foto de perfil (avatar).
    - Validación: máx 5MB, formatos JPEG/PNG/WEBP.
    - Backend: redimensionar y comprimir (WEBP, máx 400x400px).

2. **Historial de Alquileres**
    - Sección/pestaña para historial.
    - Separar activas/futuras de completadas/canceladas.
    - Filtros por deporte y fecha.

3. **Ranking y Puntos por Deporte**
    - Sistema de puntos por concretar reservas.
    - Visualización dinámica por deporte.

4. **Gestión de Amigos, Parejas y Equipos**
    - Sección "Mis Amigos / Mis Equipos".
    - Lógica de cupos según deporte.
    - Autocompletado y ghost users.
    - Historial de parejas/equipos.

5. **Complejos Favoritos**
    - Botón de favoritos en la vista de complejos.
    - Listado/carrusel de favoritos en el perfil.

6. **Recomendaciones Extra**
    - Nivel de juego (categoría).
    - Notificaciones a amigos.
    - Split payment visual.
    - Estadísticas rápidas.

---

## Proceso de Desarrollo (SDD)

- Se avanza por módulos, en orden de prioridad.
- Cada módulo se documenta con spec, diseño técnico y tareas.
- Se implementa y testea por partes.

---

## Primer Módulo a Implementar

**Edición de perfil y avatar**

- Formulario de edición de datos básicos.
- Subida y procesamiento de avatar.
- Validaciones y compresión en backend.

---

## Estructura de Carpetas Relevante

- `app/Http/Controllers/Web/ClientPanelController.php` — Lógica del panel de cliente
- `app/Models/User.php` — Modelo de usuario
- `app/Models/Reservation.php` — Modelo de reserva regular
- `app/Models/RecurringReservation.php` — Modelo de reserva recurrente
- `resources/js/Pages/Client/Dashboard.vue` — Vista principal del panel de cliente
- `resources/js/Pages/Client/Profile.vue` (a crear) — Vista de edición de perfil
- `public/storage/avatars/` — Carpeta sugerida para guardar avatares
- `database/seeders/TestRecurringReservationSeeder.php` — Seeder de prueba

---

## Notas Técnicas

- Laravel 12, PHP 8.2, Inertia.js, Vue 3, Tailwind, Vite
- Procesamiento de imágenes: Intervention Image
- Validaciones: backend y frontend
- El backend ya soporta reservas regulares y recurrentes, pero solo las regulares tienen pagos asociados

---

## Pendiente para la próxima sesión

- Especificar y diseñar el módulo de edición de perfil y avatar
- Implementar backend y frontend para ese módulo
- Continuar con los siguientes módulos según prioridad

---

## Última actualización: 24/03/2026

---

¡Listo para retomar desde acá mañana!
