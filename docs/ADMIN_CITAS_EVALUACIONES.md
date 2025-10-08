# Vista de Citas del Admin con Evaluaciones

## 📋 Resumen de Cambios

Se actualizó la vista de administración de citas para mostrar información de evaluaciones, similar a la vista del psicólogo.

## ✨ Nuevas Funcionalidades

### 1. **Columna de Evaluaciones**
- ✅ Muestra el conteo de evaluaciones por cita
- ✅ Botón para ver detalles de las evaluaciones
- ✅ Badge "Sin evaluaciones" cuando no hay registros

### 2. **Modal de Evaluaciones**
- ✅ Muestra todas las evaluaciones de una cita
- ✅ Información detallada:
  - Estado emocional (1-10) con barra de progreso visual
  - Comentarios completos
  - Fecha y hora de creación
- ✅ Colores según nivel emocional:
  - 🟢 Verde (7-10): Estado positivo
  - 🟡 Amarillo (4-6): Estado regular
  - 🔴 Rojo (1-3): Estado crítico

### 3. **Validación de Cancelación**
- ✅ **No permite cancelar citas con evaluaciones** (Frontend y Backend)
- ✅ Muestra badge con conteo de evaluaciones cuando no se puede cancelar
- ✅ Mensaje de error claro si se intenta cancelar con evaluaciones
- ✅ Al cancelar, marca `estado='inactivo'` para liberar el horario

### 4. **Mejoras en Acciones**
- ✅ Botones "Cancelar" y "Reasignar" mejorados
- ✅ Estados visuales claros (badges) para citas canceladas/realizadas
- ✅ Validación de permisos según estado de la cita

## 🔧 Archivos Modificados

### Controllers/AdminController.php
```php
// Nuevo endpoint AJAX para obtener evaluaciones
if(isset($_GET['ajax']) && $_GET['ajax']==='evaluaciones'){
    // Retorna las evaluaciones de una cita específica
}

// Método filtrarCitasAdmin modificado
- Ahora incluye count_evaluaciones para cada cita
- Usa el modelo Evaluacion para contar

// Case 'cancelar' mejorado
- Valida que no tenga evaluaciones antes de cancelar
- Marca estado='inactivo' para liberar horario
```

### Views/admin/citas.php
```javascript
// Nueva función verEvaluaciones(idCita)
- Abre modal con las evaluaciones
- Carga vía AJAX desde el backend
- Formatea la información de manera visual

// Función renderTabla() mejorada
- Agrega columna de evaluaciones
- Muestra botón o badge según cantidad

// Función accionesHtml() mejorada
- Considera evaluaciones para mostrar/ocultar botón cancelar
- Badges informativos para estados
```

## 🎯 Comportamiento por Estado

### Cita Pendiente
- ✅ **Sin evaluaciones**: Botón "Cancelar" activo
- ✅ **Con evaluaciones**: Badge mostrando conteo, no permite cancelar
- ✅ Botón "Reasignar" siempre activo

### Cita Realizada
- ✅ Badge "Realizada"
- ✅ Botón "Reasignar" deshabilitado
- ✅ Puede ver evaluaciones si las tiene

### Cita Cancelada
- ✅ Badge "Cancelada"
- ✅ Botón "Reasignar" deshabilitado
- ✅ Horario liberado automáticamente

## 🔄 Flujo de Trabajo

1. **Admin visualiza lista de citas**
   - Ve columna con conteo de evaluaciones
   
2. **Admin hace clic en botón de evaluaciones**
   - Modal se abre con spinner de carga
   - Se cargan las evaluaciones vía AJAX
   - Se muestran formateadas con barras de progreso

3. **Admin intenta cancelar cita**
   - Si tiene evaluaciones: Se muestra mensaje de error
   - Si no tiene evaluaciones: Se abre modal de confirmación
   - Al confirmar: Se marca como cancelada y se libera el horario

## 📊 Integración con Sistema de Evaluaciones

El admin ahora tiene:
- ✅ Visibilidad completa de las evaluaciones por cita
- ✅ Control sobre cancelaciones basado en evaluaciones
- ✅ Información visual clara del estado emocional del paciente
- ✅ Historial completo de la atención

## 🚀 Mejoras de UX

1. **Visual**: Barras de progreso con colores según estado emocional
2. **Información**: Fechas formateadas en español
3. **Validación**: Mensajes claros cuando no se puede realizar una acción
4. **Consistencia**: Mismo comportamiento que la vista del psicólogo

## ✅ Pruebas Recomendadas

1. **Ver evaluaciones**:
   - Cita sin evaluaciones → Debe mostrar mensaje informativo
   - Cita con evaluaciones → Debe mostrar todas con formato correcto

2. **Cancelar cita**:
   - Cita sin evaluaciones → Permite cancelar
   - Cita con evaluaciones → Bloquea y muestra badge
   - Verificar que al cancelar, el horario quede disponible

3. **Filtros**:
   - Filtrar por estado "cancelada" → Debe mostrar canceladas
   - Verificar que el conteo de evaluaciones se mantenga en filtros
