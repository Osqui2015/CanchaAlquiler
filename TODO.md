# TODO — Edición de Perfil y Avatar (Panel Cliente)

- [x] **Preparación / Revisar SDD:**
- [x] **Auditoría del modelo `User`:**
- [x] **Limpieza de migraciones duplicadas:**
- [x] **Crear migración DB — `avatar` column:**
- [x] **Actualizar políticas de storage/config:**
- [x] **Implementar accessor en `User` model:**
- [x] **Servicio/Helper de manejo de avatar (backend):** 
- [x] **FormRequest / Validaciones servidor:**
- [x] **Controlador API — endpoint de actualización de perfil:**
- [x] **Rutas y permisos:**
- [ ] **Manejo de eliminación/rollback:** Definir comportamiento al borrar usuario: eliminar avatar del storage. Añadir listener o `deleting` model event. Criterio: no quedan archivos huérfanos al borrar usuario.
- [ ] **Política de limpieza / retención:** Definir proceso para eliminar avatars antiguos o temporales (CRON o job). Criterio: documentación con comando o job programado.
- [ ] **Tests backend — Unitarios servicio:** Tests para: guardado de archivo, reemplazo y eliminación del anterior, generación de URL, y manejo de errores (espacio disco, nombre no válido). Usar `Storage::fake('public')`. Criterio: pasar tests unitarios.
- [ ] **Tests feature API:** Tests HTTP para endpoint de `updateProfile`: subida exitosa, validaciones fallidas, usuario no autenticado. Criterio: cobertura funcional para casos críticos.
- [x] **Diseño UI/UX — especificación:** 
- [x] **Componente Inertia + Vue — estructura:** Crear componente `Profile/EditProfile` con:
    - form con campos del perfil
    - input file para avatar
    - preview (mostrar `avatar_url` actual y preview local)
    - validación cliente (tamaño/formato)
    - subida por `FormData` con progreso (axios/fetch + progress)
    - manejo de errores y éxito (Inertia `replace` o `reload` según flujo)
      Dependencia: rutas Ziggy y endpoint API. Criterio: componente funcional en dev local.
- [x] Agregar menú lateral (Sidebar) con perfil de usuario
- [x] Implementar página de "Mi Historial"
- [x] Subida de avatar con preview en tiempo real
- [x] Layout unificado para el panel de cliente
- [ ] Alert de reserva pendiente en la Home (Landing)
- [ ] Lógica de expiración automática para reservas pasadas (Cron/Command)
- [ ] **UX: preview y crop (opcional):** Si SDD requiere crop, integrar librería ligera de crop en cliente y enviar imagen recortada; si no, enviar original y delegar redimensionado al backend. Criterio: preview en tiempo real y crop guardado.
- [ ] **Optimización cliente:** Aplicar compresión/resize en cliente para mejorar upload (si SDD lo permite). Criterio: reducción de tamaño sin pérdida visible.
- [ ] **Frontend tests — unit / integration:** Tests de componente Vue: renderizado, selección de archivo, preview y llamada al endpoint (mock axios). Criterio: tests pasan en CI local.
- [ ] **E2E / Playwright o Dusk:** Test que simula login, navegación al perfil, subida de avatar y verificación de la imagen visible en la UI. Criterio: escenario E2E pasa.
- [ ] **Integración y QA manual:** Probar en staging: distintos tamaños, formatos inválidos, conexión lenta, reemplazo de avatar, borrado de usuario. Criterio: checklist QA aprobado.
- [ ] **Compatibilidad y migración en producción:** Preparar notas de despliegue:
    - ejecutar migración en ventana de baja actividad
    - configurar storage (symlink `storage:link` si aplica)
    - backup DB previo
    - limpiar migraciones duplicadas si existieran en prod
      Criterio: checklist de despliegue completado.
- [ ] **Documentación técnica:** Actualizar:
    - especificación API (endpoint, método, payload, respuestas)
    - actualización en SDD con decisiones (crop server/client, max size)
    - README de desarrollador con pasos para probar localmente (`php artisan migrate`, `php artisan storage:link`, `Storage::fake` para tests)
    - changelog con la migración añadida
      Criterio: docs revisadas y accesibles en repo.
- [ ] **Observabilidad / Logs / Métricas:** Añadir logs en puntos críticos (upload success/fail), y métricas básicas si existe stack (opcional). Criterio: errores de avatar registrables en logs.
- [ ] **Refactor mínimo y limpieza:** Revisar uso de avatar en todo el código (emails, thumbnails, navbar) para usar `avatar_url` accessor en lugar de referencias directas a `avatar`. Criterio: código consistente y sin warnings.
- [ ] **Revisión de seguridad:** Verificar validaciones, evitar path traversal, y revisar permisos de archivos en storage. Criterio: auditoría rápida aprobada.
- [ ] **Checklist de merge & PR:** Incluir en PR:
    - tests ejecutados y passing
    - screenshots o video corto de flujo
    - notas de migración y despliegue
    - referencia a SDD
      Criterio: PR listo para revisión.
- [ ] **Post-merge tareas:** Ejecutar migraciones en staging/producción, monitorear logs por 24–48h, ejecutar job de limpieza si corresponde. Criterio: sin errores relevantes tras despliegue.
