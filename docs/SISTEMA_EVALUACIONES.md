# Sistema de Evaluaciones y Atención de Citas

## Descripción General

Se ha implementado un nuevo flujo para la atención de citas con el sistema de evaluaciones integrado. Ahora los psicólogos pueden:

1. **Escanear o buscar una cita** mediante código QR o desde la tabla
2. **Atender la cita** en una vista dedicada donde pueden:
   - Ver información completa del paciente y la cita
   - Agregar múltiples evaluaciones durante la sesión
   - Ver todas las evaluaciones registradas
3. **Finalizar la cita** cuando se complete la atención

## Cambios Implementados

### 1. Nuevo Modelo: `Evaluacion.php`

**Ubicación:** `Models/Evaluacion.php`

Métodos principales:
- `obtenerPorCita($idCita)` - Obtiene todas las evaluaciones de una cita
- `crear($idCita, $estadoEmocional, $comentarios)` - Crea una nueva evaluación
- `contarPorCita($idCita)` - Cuenta cuántas evaluaciones tiene una cita
- `estadisticasPorPsicologo($idPsicologo)` - Estadísticas de evaluaciones

**Validaciones:**
- Estado emocional debe estar entre 1 y 10
- Comentarios son obligatorios

### 2. Controlador Actualizado: `PsicologoController.php`

#### Nuevos métodos:

**`atenderCita()`**
- Muestra la vista de atención de cita
- Verifica que la cita pertenezca al psicólogo
- Obtiene evaluaciones existentes
- Determina si se puede editar (solo si no está realizada o cancelada)

**`guardarEvaluacion()`**
- Endpoint AJAX para guardar evaluaciones
- Valida que la cita no esté finalizada
- Retorna JSON con el resultado

**`finalizarCita()`**
- Marca la cita como "realizada"
- Requiere al menos una evaluación registrada
- Solo permite finalizar citas pendientes

#### Método modificado:

**`scanConsultar()`**
- Ahora retorna información del paciente (nombre)
- Prepara datos para mostrar botón "Atender Cita"
- No confirma automáticamente la asistencia

### 3. Nueva Vista: `atender_cita.php`

**Ubicación:** `Views/psicologo/atender_cita.php`

**Características:**

#### Columna Izquierda:
- Información completa de la cita
- Datos del paciente (nombre, DUI, teléfono, email)
- Fecha/hora y estado de la cita
- Motivo de consulta
- Botón "Finalizar Cita" (solo si está pendiente)

#### Columna Derecha:
- **Formulario de evaluación** (si puede editar):
  - Slider para estado emocional (1-10)
  - Área de texto para comentarios
  - Botones limpiar y guardar
- **Lista de evaluaciones registradas**:
  - Muestra todas las evaluaciones con su ID
  - Estado emocional y comentarios
  - Contador de evaluaciones

**Funcionalidades JavaScript:**
- Envío AJAX del formulario
- Actualización dinámica de la lista
- Mensajes de éxito/error
- Scroll automático a nueva evaluación

### 4. Vista Actualizada: `citas.php`

#### Cambios en la tabla:

**Nueva columna "Acciones":**
- **Botón "Atender"** (azul) - Para citas pendientes
- **Botón "Ver"** (info) - Para citas realizadas
- **Botón "Pagar"** - Solo para citas realizadas sin pago
- **Link "Ticket"** - Si ya está pagado

#### Scanner Modal actualizado:

**Cambios:**
- Ya no muestra botón "Confirmar asistencia"
- Ahora muestra botón **"Atender Cita"**
- Al escanear QR, muestra información de la cita
- Redirige a la vista de atención al hacer clic

**Función `procesarTokenModal()`:**
- Llama a `scanConsultar` en lugar de confirmar directamente
- Muestra nombre del paciente
- Habilita botón "Atender" si no está cancelada

**Función `atenderCitaModal()`:**
- Redirige a `psicologo/atenderCita&id=X`

## Flujo de Trabajo

### Opción 1: Escanear QR

1. Psicólogo hace clic en "Escanear QR"
2. Scanner detecta código QR de la cita
3. Se muestra información de la cita en el modal
4. Psicólogo hace clic en "Atender Cita"
5. Se abre la vista de atención

### Opción 2: Desde la tabla

1. Psicólogo busca la cita en la tabla
2. Hace clic en el botón "Atender" (azul)
3. Se abre la vista de atención

### Durante la atención:

1. Psicólogo ve información del paciente
2. Agrega evaluación:
   - Selecciona estado emocional (1-10)
   - Escribe comentarios de la sesión
   - Hace clic en "Guardar Evaluación"
3. Puede agregar múltiples evaluaciones durante la sesión
4. Cuando termina, hace clic en "Finalizar Cita"

### Validaciones:

- **No se puede finalizar sin evaluaciones**: Sistema requiere al menos una
- **No se pueden agregar evaluaciones a citas finalizadas**: Solo citas pendientes
- **Solo el psicólogo dueño puede atender**: Validación de pertenencia

### Ver citas realizadas:

1. Psicólogo hace clic en "Ver" (botón info)
2. Se abre la misma vista pero en modo lectura
3. Puede ver todas las evaluaciones registradas
4. No puede agregar nuevas evaluaciones

## Base de Datos

### Tabla Evaluacion

```sql
CREATE TABLE Evaluacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_cita INT NOT NULL,
    estado_emocional INT CHECK (estado_emocional BETWEEN 1 AND 10),
    comentarios TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_evaluacion_cita FOREIGN KEY (id_cita)
        REFERENCES Cita(id) ON UPDATE CASCADE ON DELETE CASCADE
);
```

**Campos:**
- `id_cita`: FK a la tabla Cita
- `estado_emocional`: Valor de 1 a 10
- `comentarios`: Observaciones de la sesión
- `estado`: Soft delete

## Rutas

### Nuevas rutas:

- `GET psicologo/atenderCita?id=X` - Vista de atención
- `POST psicologo/guardarEvaluacion` - AJAX para guardar evaluación
- `POST psicologo/finalizarCita` - Finalizar y marcar como realizada

### Rutas modificadas:

- `POST psicologo/scanConsultar` - Ahora retorna más información

## Mensajes y Códigos de Error

### En atender_cita.php:

**Éxito:**
- `ok=finalizada` - "Cita finalizada correctamente"

**Errores:**
- `err=nf` - "Cita no encontrada"
- `err=sin_eval` - "Debes agregar al menos una evaluación antes de finalizar"
- `err=update` - "Error al actualizar el estado de la cita"

**Mensajes:**
- `msg=ya_realizada` - "Esta cita ya está marcada como realizada"

### En guardarEvaluacion (AJAX):

**JSON Response:**
```json
{
  "ok": true/false,
  "msg": "Mensaje descriptivo",
  "evaluacion": {...}  // Si ok=true
}
```

## Estadísticas de Evaluaciones

El modelo incluye método para obtener estadísticas:

```php
$evalModel->estadisticasPorPsicologo($idPsicologo);
```

**Retorna:**
- Total de evaluaciones
- Promedio de estado emocional
- Mínimo y máximo estado

Esto puede usarse en el dashboard o en reportes.

## Mejoras Futuras Sugeridas

1. **Editar evaluaciones**: Permitir modificar evaluaciones existentes
2. **Eliminar evaluaciones**: Soft delete de evaluaciones
3. **Gráficos**: Visualizar evolución del estado emocional del paciente
4. **Plantillas**: Comentarios predefinidos o plantillas de evaluación
5. **Exportación**: Incluir evaluaciones en reportes PDF/Excel
6. **Notificaciones**: Alertar si una cita no tiene evaluaciones
7. **Historial**: Ver todas las evaluaciones de un paciente en su perfil

## Notas Técnicas

- **Soft Delete**: Las evaluaciones usan estado='activo'/'inactivo'
- **AJAX**: La vista de atención no recarga la página al guardar evaluaciones
- **Bootstrap 5**: Se usa Bootstrap modal y componentes
- **Font Awesome**: Iconos en botones e interfaces
- **Validación**: Cliente y servidor validan estado emocional (1-10)

## Testing Recomendado

1. Crear una cita
2. Escanear QR o hacer clic en "Atender"
3. Agregar múltiples evaluaciones
4. Intentar finalizar (debe funcionar)
5. Verificar que no se puedan agregar más evaluaciones
6. Ver la cita con botón "Ver"
7. Verificar que todo es solo lectura

---

**Fecha de implementación:** Octubre 2025  
**Autor:** Sistema de Gestión Psicológica
