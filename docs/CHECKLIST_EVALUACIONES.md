# ✅ Checklist de Implementación - Sistema de Evaluaciones

## Antes de Probar

### 1. Base de Datos
- [ ] Ejecutar script `config/verificar_evaluaciones.sql` en phpMyAdmin
- [ ] Verificar que la tabla `Evaluacion` se creó correctamente
- [ ] La tabla debe tener estos campos:
  - `id` (INT, PK, AUTO_INCREMENT)
  - `id_cita` (INT, FK a Cita)
  - `estado_emocional` (INT, CHECK 1-10)
  - `comentarios` (TEXT)
  - `estado` (ENUM 'activo', 'inactivo')

### 2. Archivos Verificados
- [x] ✅ `Models/Evaluacion.php` - Creado
- [x] ✅ `Views/psicologo/atender_cita.php` - Creado
- [x] ✅ `Controllers/PsicologoController.php` - Actualizado
- [x] ✅ `Views/psicologo/citas.php` - Actualizado

### 3. Dependencias
- [ ] Verificar que Font Awesome esté cargado (iconos)
- [ ] Verificar que Bootstrap 5 esté cargado (modals)
- [ ] Verificar que jQuery/Bootstrap JS estén disponibles

## Pruebas Paso a Paso

### Prueba 1: Crear y Atender Cita

1. **Login como psicólogo**
   - [ ] Ir a `/index.php?url=auth/login`
   - [ ] Usar credenciales de psicólogo

2. **Ver listado de citas**
   - [ ] Ir a "Mis Citas" o `/index.php?url=psicologo/citas`
   - [ ] Verificar que aparece la tabla

3. **Crear una cita nueva** (si no hay ninguna)
   - [ ] Seleccionar paciente
   - [ ] Elegir fecha y hora
   - [ ] Agregar motivo
   - [ ] Guardar
   - [ ] Verificar que aparece en la tabla

4. **Atender cita desde tabla**
   - [ ] Buscar una cita con estado "pendiente"
   - [ ] Hacer clic en botón azul "Atender"
   - [ ] Debería abrir vista de atención

### Prueba 2: Vista de Atención

5. **Verificar información**
   - [ ] Ver datos del paciente en columna izquierda
   - [ ] Ver fecha/hora de la cita
   - [ ] Ver motivo de consulta (si tiene)
   - [ ] Estado debe ser "PENDIENTE"

6. **Agregar primera evaluación**
   - [ ] Mover slider de estado emocional (1-10)
   - [ ] Escribir comentarios (ej: "Primera sesión de terapia cognitiva")
   - [ ] Clic en "Guardar Evaluación"
   - [ ] Esperar mensaje de éxito
   - [ ] Verificar que aparece en la lista abajo

7. **Agregar más evaluaciones**
   - [ ] Limpiar formulario (o hacer clic en "Limpiar")
   - [ ] Agregar segunda evaluación diferente
   - [ ] Agregar tercera evaluación
   - [ ] Verificar que todas aparecen en la lista
   - [ ] Contador debe mostrar "3"

8. **Intentar finalizar sin evaluaciones** (opcional - resetear BD)
   - [ ] Si eliminas todas las evaluaciones
   - [ ] Intentar finalizar
   - [ ] Debería mostrar error: "Debes agregar al menos una evaluación"

9. **Finalizar cita correctamente**
   - [ ] Con al menos 1 evaluación guardada
   - [ ] Clic en "Finalizar Cita"
   - [ ] Confirmar en el diálogo
   - [ ] Debería redirigir a `/psicologo/citas`
   - [ ] Verificar mensaje de éxito

### Prueba 3: Scanner QR

10. **Escanear código QR**
    - [ ] Hacer clic en "Escanear QR"
    - [ ] Permitir acceso a la cámara
    - [ ] Escanear QR de una cita (o ingresar manualmente `CITA:1`)
    - [ ] Verificar que muestra información
    - [ ] Botón "Atender Cita" debe aparecer
    - [ ] Clic en "Atender Cita"
    - [ ] Debería abrir la vista de atención

### Prueba 4: Ver Cita Realizada (Solo Lectura)

11. **Ver cita finalizada**
    - [ ] Buscar cita con estado "realizada"
    - [ ] Hacer clic en botón "Ver" (azul claro/info)
    - [ ] Verificar que abre vista de atención
    - [ ] **NO** debería mostrar formulario de evaluación
    - [ ] **SÍ** debería mostrar todas las evaluaciones registradas
    - [ ] **NO** debería mostrar botón "Finalizar Cita"

### Prueba 5: Validaciones

12. **Probar validaciones**
    - [ ] Intentar guardar evaluación sin comentarios → Error
    - [ ] Intentar acceder a cita de otro psicólogo → Error
    - [ ] Intentar agregar evaluación a cita cancelada → Error
    - [ ] Intentar agregar evaluación a cita realizada → Error

## Problemas Comunes

### ❌ Error: "Tabla no existe"
**Solución:** Ejecutar `config/verificar_evaluaciones.sql`

### ❌ Error: "Método no encontrado"
**Solución:** Verificar que `PsicologoController.php` tiene los nuevos métodos

### ❌ No aparece botón "Atender"
**Solución:** Limpiar caché del navegador (Ctrl+Shift+R)

### ❌ Scanner no funciona
**Solución:** 
- Verificar que existe `public/js/html5-qrcode.min.js`
- Permitir acceso a cámara en el navegador

### ❌ AJAX no guarda
**Solución:**
- Abrir consola del navegador (F12)
- Ver si hay errores JavaScript
- Verificar que la ruta POST funciona

### ❌ Botones sin iconos
**Solución:** 
- Verificar que Font Awesome está cargado
- Agregar en layout: `<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">`

## Resultados Esperados

### ✅ Funcionamiento Correcto:

1. **Tabla de citas:**
   - Citas pendientes tienen botón "Atender" (azul)
   - Citas realizadas tienen botón "Ver" (info)
   - Botones de pago aparecen según corresponda

2. **Vista de atención:**
   - Muestra info completa del paciente
   - Permite agregar múltiples evaluaciones
   - No permite editar después de finalizar
   - Redirige correctamente al finalizar

3. **Scanner:**
   - Detecta QR de citas
   - Muestra botón "Atender Cita"
   - Redirige a vista correcta

4. **Base de datos:**
   - Evaluaciones se guardan correctamente
   - FK a citas funciona
   - Soft delete (estado='inactivo') funciona

## Siguiente Nivel (Opcional)

Si todo funciona, podrías agregar:

- [ ] Exportar evaluaciones a PDF
- [ ] Gráfico de evolución del paciente
- [ ] Plantillas de comentarios comunes
- [ ] Historial de evaluaciones en perfil de paciente
- [ ] Dashboard con estadísticas de evaluaciones

---

**¡Marca cada checkbox conforme vayas probando!** ✅

Una vez completado este checklist, el sistema de evaluaciones estará completamente funcional.
