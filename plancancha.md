1. Resumen del objetivo
Agregar soporte en la web/app de "CanchaAlquiler" para:

Rankings diferenciados según modalidad (individual, pareja, equipos) para cada deporte (ej: fútbol 5/7/11, padel individual/pareja, básquet 3vs3/5vs5, etc).
Selector de modalidad adaptativo en el dashboard de admin_cancha.
Visualización y consulta del historial de integrantes de equipos/parejas en partidos.
Estructura de base y endpoints clara, flexible para futuros deportes/modalidades o cambios.
2. Estado actual
El sistema permite ranking global, pero no distingue modalidades ni historial de cambios de equipos.
Las tablas y endpoints actuales no filtran rankings ni KPIs por modalidad ni muestran historial de equipos/jugadores.
3. Qué hay que implementar
BASE DE DATOS
 Agregar columna modalidad en tabla de partidos o reservas:
ALTER TABLE partidos ADD COLUMN modalidad VARCHAR(20) NOT NULL DEFAULT 'individual';
 Crear tabla para historial de integrantes en equipos/parejas en cada partido:
CREATE TABLE historial_equipo_usuario (
  id INT AUTO_INCREMENT PRIMARY KEY,
  equipo_id INT NOT NULL,
  usuario_id INT NOT NULL,
  partido_id INT NOT NULL,
  fecha DATETIME NOT NULL,
  FOREIGN KEY (equipo_id) REFERENCES equipos(id),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (partido_id) REFERENCES partidos(id)
);
 (Opcional) Validar consistencia en tablas equipos, usuarios y deportes: chequear keys, integridad y existencia de modalidad en deportes.
BACKEND (LARAVEL)
 Endpoints sugeridos:
GET /api/deportes/{id}/modalidades
Devuelve modalidades habilitadas por deporte.
GET /api/rankings?deporte=futbol&modalidad=equipo_7
Devuelve ranking filtrado por modalidad y deporte.
GET /api/equipo/{id}/historial
Detalla historial de integrantes y partidos de ese equipo.
 Lógica de cálculo:
Contemplar métricas distintas por modalidad (partidos jugados, ganados, goles, eficiencia, etc. según tipo de ranking).
Ranking de parejas y equipos debe identificar correctamente integrantes.
Ranking individual debe diferenciarse de ranking por equipo.
FRONTEND (VUE)
 Agregar/ajustar componentes:
Selector de deporte: (dropdown o tabs).
Selector de modalidad: según deporte (por ejemplo, “Padel → Individual / Pareja”, “Fútbol → 5, 7, 11”; “Básquet → 3vs3/5vs5”).
Tabla ranking adaptativa: muestra columnas adecuadas según modalidad elegida.
Botón / link "Ver Historial": para equipos/parejas, muestra partidos y cambios de integrantes.
Componente de historial: modal, drawer o página secundaria con detalle por partido.
 UX/UI:
Asegurarse que los selectores filtren correctamente y sea claro a qué corresponde cada modalidad.
Mostrar correctamente KPIs distintos según modalidad seleccionada.
Ver historial de cambios (quién jugó cada partido de cada equipo o pareja).
4. Pruebas y validación
 Simular alta de deportes, modalidades, partidos y equipos de diferentes tipos y con cambios históricos de integrantes.
 Chequear visualización de rankings y KPIs en cada modalidad.
 Verificar que el historial de equipos/parejas sea correcto y tenga buena navegabilidad.
 Validar comportamientos edge: jugadores en varias parejas, miembros en diferentes equipos, cambios a mitad de temporada, etc.
5. Mejoras futuras / Ideas para expandir
Integrar exportación a Excel/CSV por ranking/modalidad.
Permitir cargar fotos/logos a equipos.
Agendar partidos y mostrar historial de enfrentamientos entre equipos.
Notificaciones automáticas cuando haya cambios de integrantes.
Lógica avanzada de historial (por ejemplo, ranking histórico por temporada, o por período determinado).
6. Sugerencias de código (para implementar)
Ejemplo de migración Laravel para nueva tabla historial
// database/migrations/xxxx_xx_xx_create_historial_equipo_usuario.php

public function up()
{
    Schema::create('historial_equipo_usuario', function (Blueprint $table) {
        $table->id();
        $table->foreignId('equipo_id')->constrained();
        $table->foreignId('usuario_id')->constrained();
        $table->foreignId('partido_id')->constrained();
        $table->dateTime('fecha');
    });
}
Ejemplo de consulta Eloquent para ranking por modalidad
$partidos = Partido::where('deporte_id', $deporteId)
   ->where('modalidad', $modalidad)
   ->with(['equipos.usuarios'])
   ->get();

// Calcular ranking con lógica particular acá después del ->get()
Ejemplo de endpoint para historial
Route::get('/api/equipo/{id}/historial', function($id) {
    return HistorialEquipoUsuario::where('equipo_id', $id)
        ->with(['usuario', 'partido'])
        ->orderBy('fecha')
        ->get();
});
Ejemplo de frontend: selector modalidades en Vue
<template>
  <select v-model="modalidadSeleccionada" @change="recargarRanking">
    <option v-for="mod in modalidadesDisponibles" :key="mod" :value="mod">
      {{ mostrarNombreModalidad(mod) }}
    </option>
  </select>
</template>
7. Checklist: ¿Qué falta hacer?
 Ejecutar migraciones en base de datos (SQL o artisan migrate).
 Extender backend con lógica diferenciada y endpoints nuevos.
 Actualizar frontend con experiencia UX de selector/modalidad + historial.
 Cargar datos de prueba y validar casos límite.
 Documentar para admins el uso del nuevo sistema.
Recomendación general:
Realizar cada paso en un entorno de pruebas. Asegurarse de tener backups actualizados antes de modificar tablas o lógica que afecte histórico de partidos/equipos.
