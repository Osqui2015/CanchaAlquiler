Plan de trabajo - Continuar mañana
Fecha: 2026-03-31

Resumen breve de lo hecho hasta ahora

- Backend:
    - Endpoint de rankings disponible (`GET /api/rankings`) y proxy web route `/panel/admin-cancha/rankings` creado.
    - Seeder `PadelPlayersSeeder` añadido y ejecutado (8 usuarios padel + `user_rankings`).
    - Migraciones recientes aplicadas (modalidad, equipos, team_user_histories).
- Frontend:
    - `Dashboard.vue` (admin) modularizado en partials y errores TS corregidos para permitir build.
    - Se añadieron partials: `ReservationsPart`, `ClientsPart`, `ModalidadSelector`, `RankingByModalidad`.
    - Client panel: añadidas pestañas y componentes `ClientProfile`, `ClientRanking`, `ClientFavorites`.
    - Tipo-safety: primer paso de hardening (creado `types.ts`, aplicado en `ClientsPart.vue`).
- Developer workflow:
    - Rama `feature/cleanup-build` creada y commiteada; build y tests locales pasan.
    - Se generó `database/padel_players.json` y se añadieron `README-DEV.md` y script `scripts/export_padel.php`.

Objetivo de mañana

- Completar y asegurar la experiencia del panel del cliente: que `Configuraciones`, `Ranking`, `Reservas` y `Favoritos` estén integrados y funcionales.
- Terminar el hardening de TypeScript en los partials y en `Dashboard.vue`.
- Realizar QA manual: probar flujo de reserva, ranking y edición de perfil.
- Preparar Pull Request con la rama `feature/cleanup-build` y descripción de cambios.

Tareas priorizadas (ordenadas)

1. Backend: endpoints faltantes
    - Verificar/crear endpoint `POST /panel/cliente/profile` para actualizar perfil (avatar, teléfono, password).
    - Verificar/crear endpoint `GET /api/client/favorites` y `POST /api/client/favorites` si no existen.
      Archivo(s) a tocar: `app/Http/Controllers/Api/` o `app/Http/Controllers/Client/*`.

2. Frontend: completar ClientProfile
    - Pre-cargar los datos actuales del usuario en el formulario.
    - Asegurar subida de avatar con `FormData` y método PUT.
    - Manejar mensajes de éxito/error y validar campos.
      Archivos: `resources/js/Pages/Client/parts/ClientProfile.vue` y rutas/acciones en Inertia.

3. Frontend: Ranking
    - Permitir seleccionar modalidad (pareja/individual/equipo) si aplica, y pasar como `modalidad` al endpoint.
    - Destacar la posición del usuario y mostrar si está en pareja/equipo (si la data lo soporta).
      Archivos: `ClientRanking.vue`, `RankingController` (si hace falta ampliar la respuesta).

4. Frontend: Favoritos
    - Conectar la UI con el endpoint real `/api/client/favorites` o crear mock temporal.
    - Añadir acción en UI para agregar/retirar favorito desde páginas de complejo.

5. Hardening TS
    - Reemplazar `any` / `as any` por tipos concretos en:
        - `ReservationsPart.vue`
        - `ModalidadSelector.vue`
        - `RankingByModalidad.vue`
        - `Dashboard.vue` (client + admin partials)
    - Ejecutar `npm run type-check` y corregir errores hasta limpio.

6. QA manual y pruebas
    - Ejecutar migraciones y seeders: `composer dump-autoload && php artisan migrate --seed`.
    - Levantar servidores: `npm run dev -- --host` y `php artisan serve --host=127.0.0.1 --port=8000`.
    - Probar en navegador: `http://127.0.0.1:8000/panel/cliente`:
        - Editar perfil (subida avatar, cambio password, teléfono).
        - Ver ranking: seleccionar deporte/modalidad y localizar posición propia.
        - Reservas: cancelar, iniciar checkout (si aplica), ver historial.
        - Favoritos: ver y (opcional) agregar/retirar.

7. Pull Request
    - Preparar PR desde `feature/cleanup-build` a `main` con descripción: resumen de cambios, puntos abiertos (endpoints por crear), pruebas realizadas.

Comandos útiles

```powershell
# Regenerar autoload y aplicar migraciones + seed
composer dump-autoload
php artisan migrate --seed

# Levantar frontend y backend (en terminales separadas)
npm run dev -- --host
php artisan serve --host=127.0.0.1 --port=8000

# Type check y build
npm run type-check
npm run build

# Ejecutar tests
php artisan test
```

Notas / riesgos

- Algunos endpoints para perfil/favoritos pueden no existir; crear los controladores y rutas será necesario.
- El seeder y el modelo `user_rankings` ya están en uso; si se desea más detalle (parejas/equipos) hay que ampliar la tabla y el seeder.

Asignación sugerida para mañana (ejecución secuencial)

- Mañana mañana (mañana): 1h — Backend: confirmar/crear endpoints de profile y favorites.
- Mañana mediodía: 2h — Frontend: completar `ClientProfile` pre-carga y submit.
- Tarde: 2h — Ranking + hardening TS + QA rápido.
- Última hora: 30-60min — Preparar PR y documentación breve en la descripción.

Archivo con estado actual (referencia rápida)

- `feature/cleanup-build` contiene todos los cambios frontend y seeders.
- Archivos modificados principales:
    - resources/js/Pages/AdminCancha/\* (partials)
    - resources/js/Pages/Client/Dashboard.vue
    - resources/js/Pages/Client/parts/ClientProfile.vue
    - resources/js/Pages/Client/parts/ClientRanking.vue
    - resources/js/Pages/Client/parts/ClientFavorites.vue
    - database/seeders/PadelPlayersSeeder.php

---

Si querés, mañana empiezo por (elige una):
A) Crear endpoint `PUT /panel/cliente/profile` y conectar `ClientProfile`.
B) Completar `ClientProfile.vue` para precargar datos y manejar respuesta.
C) Continuar con hardening TS en los partials.
D) Hacer QA completo y documentar bugs encontrados.
