README-DEV — Guía de desarrollo extendida (Windows / Docker)

1. Entorno Windows (PowerShell)

- Usá `npm.cmd` en PowerShell si `npm` lanza problemas de ejecución:

    npm.cmd install

- Composer en Windows (PowerShell):

    composer install

- Si `php artisan` falla por permisos desde PowerShell, abrí una terminal cmd o ejecutá PowerShell como administrador.

- Regenerar autoload tras crear seeders/clases:

    composer dump-autoload

2. Migraciones y seeders (local)

- Copiar .env y configurar DB local (MySQL/MariaDB):

    cp .env.example .env
    php artisan key:generate

- Ejecutar migraciones y seeders básicos:

    php artisan migrate --seed

- Ejecutar sólo el seeder de pádel (rápido):

    php artisan db:seed --class=PadelPlayersSeeder

3. Vite / Frontend

- Instalar dependencias JS en PowerShell (usar npm.cmd si hace falta):

    npm.cmd install

- Levantar Vite con host para HMR en Windows:

    npm.cmd run dev -- --host

- Verificación de tipos (vue-tsc):

    npm.cmd run type-check

4. Debugging común en Windows

- HMR/ERR_ADDRESS_INVALID: revisar `vite.config.js` y `server.hmr.host = 'localhost'`.
- `mysqldump` y codificación: en PowerShell redirecciones `>` pueden escribir UTF-16; preferir usar `mysqldump --result-file=out.sql`.
- Si `php artisan test` falla por `could not find driver`, cambiar `DB_CONNECTION` en `phpunit.xml` o usar una DB de pruebas local.

5. Docker (opcional)

- Recomiendo usar un contenedor MySQL y otro para PHP-FPM + Composer. Mantener la carpeta del proyecto montada como volumen.

6. Archivos útiles y ubicación

- README.md: guía general (ya actualizada)
- README-DEV.md: (este archivo) pasos extendidos para desarrollo en Windows/Docker
- Seeder padel: `database/seeders/PadelPlayersSeeder.php`
- JSON export: `database/padel_players.json` (generado por la tarea solicitada)

7. Cómo exportar usuarios de pádel manualmente (si necesitás repetir)

En PowerShell:

powershell -NoProfile -Command "php artisan tinker --execute \"echo json_encode(\App\\Models\\User::where('email','like','%padel%')->get(['id','name','email','rankid'])->toArray(), JSON_PRETTY_PRINT);\"" > database/padel_players.json

(O alternativamente ejecutar `php artisan tinker --execute "file_put_contents('database/padel_players.json', json_encode(..., JSON_PRETTY_PRINT));"`)

8. Notas finales

- Contraseñas generadas por seeders: `password` (hashed). Cambiarlas en entornos reales.
- Si el seeder falla por columnas no existentes en `user_rankings`, ejecuar `php artisan migrate`.
