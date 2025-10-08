# Sistema de Especialidades - Implementación Completa

## 📋 Resumen

Se implementó un CRUD completo para gestionar **Especialidades** de psicólogos, integrándolo con el sistema existente.

## 🗂️ Archivos Creados

### 1. **Models/Especialidad.php**
Modelo completo con los siguientes métodos:
- `listarTodas()` - Lista todas las especialidades
- `listarActivas()` - Solo especialidades activas
- `obtenerPorId($id)` - Obtener una especialidad específica
- `crear($data)` - Crear nueva especialidad
- `actualizar($id, $data)` - Actualizar especialidad
- `cambiarEstado($id, $estado)` - Activar/Desactivar
- `tienePsicologos($id)` - Verificar si tiene psicólogos asignados
- `contarPsicologos($id)` - Contar psicólogos por especialidad
- `eliminar($id)` - Eliminar (solo si no tiene psicólogos)

### 2. **Controllers/EspecialidadController.php**
Controlador con las siguientes acciones:
- `index()` - Listar especialidades
- `crear()` - Crear nueva especialidad
- `actualizar()` - Actualizar especialidad existente
- `cambiarEstado()` - Cambiar estado activo/inactivo
- `eliminar()` - Eliminar especialidad (con validación)

### 3. **Views/admin/especialidades.php**
Vista completa con:
- Formulario para crear nuevas especialidades
- Tabla editable con todas las especialidades
- Columna con conteo de psicólogos por especialidad
- Selector de estado (activo/inactivo)
- Botón eliminar (solo si no tiene psicólogos)
- Mensajes de éxito/error

## 🔧 Archivos Modificados

### 1. **index.php**
```php
// Agregado en el navbar del admin (línea ~148)
<li class="nav-item">
    <a class="nav-link <?= ($seg0==='especialidad')?'active fw-semibold':'' ?>" 
       href="<?= RUTA ?>index.php?url=especialidad">
        <i class="fas fa-graduation-cap me-1"></i>Especialidades
    </a>
</li>
```

### 2. **Controllers/AdminController.php - método `psicologos()`**
**Cambios:**
- Agregado `require_once Especialidad.php`
- Agregado `$espModel = new Especialidad()`
- Cambiado `'especialidad'` por `'id_especialidad'` en crear/editar
- Agregada validación de especialidad requerida
- Pasando `$especialidades` a la vista

**Antes:**
```php
$psModel->crear($idU,[
    'especialidad'=>trim($_POST['especialidad']??''),
    ...
]);
```

**Ahora:**
```php
$idEspecialidad = (int)($_POST['id_especialidad'] ?? 0);
if(!$idEspecialidad){
    throw new Exception('Debe seleccionar una especialidad');
}
$psModel->crear($idU,[
    'id_especialidad'=>$idEspecialidad,
    ...
]);
```

### 3. **Views/admin/psicologos.php**
**Formulario de crear:**
```php
// Antes
<input name="especialidad" class="form-control form-control-sm" placeholder="Especialidad">

// Ahora
<select name="id_especialidad" class="form-select form-select-sm" required>
  <option value="">Especialidad</option>
  <?php foreach($especialidades as $esp): ?>
    <option value="<?= (int)$esp['id'] ?>"><?= htmlspecialchars($esp['nombre']) ?></option>
  <?php endforeach; ?>
</select>
```

**Tabla de edición:**
```php
// Antes
<td><input name="especialidad" value="..." class="form-control form-control-sm"></td>

// Ahora
<td>
  <select name="id_especialidad" class="form-select form-select-sm" required>
    <option value="">Seleccionar</option>
    <?php foreach($especialidades as $esp): ?>
      <option value="<?= (int)$esp['id'] ?>" 
              <?= (int)$p['id_especialidad']===(int)$esp['id']?'selected':'' ?>>
        <?= htmlspecialchars($esp['nombre']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</td>
```

### 4. **Models/Psicologo.php**
**Cambios:**
- `colEspecialidad` ahora es `'id_especialidad'` por defecto
- `resolverColumnas()` busca primero `id_especialidad`, luego `especialidad`
- `crear()` acepta `id_especialidad` o `especialidad` (retrocompatibilidad)
- `actualizar()` acepta `id_especialidad` o `especialidad`
- `listarTodos()` hace LEFT JOIN con tabla Especialidad para obtener nombre

**Query actualizado:**
```sql
SELECT p.*, u.nombre, u.email, u.estado, e.nombre as nombre_especialidad
FROM Psicologo p
JOIN Usuario u ON u.id = p.id_usuario
LEFT JOIN Especialidad e ON e.id = p.id_especialidad
WHERE u.rol='psicologo'
ORDER BY u.nombre
```

## 🎯 Funcionalidades Implementadas

### ✅ CRUD Completo de Especialidades
1. **Crear** - Formulario con nombre y descripción
2. **Listar** - Tabla con todas las especialidades
3. **Actualizar** - Edición inline en la tabla
4. **Cambiar Estado** - Selector activo/inactivo
5. **Eliminar** - Solo si no tiene psicólogos asignados

### ✅ Integración con Psicólogos
1. **Crear psicólogo** - Select de especialidades activas
2. **Editar psicólogo** - Select con especialidad actual seleccionada
3. **Validación** - No permite crear/editar sin especialidad
4. **Visualización** - Muestra nombre de especialidad en listado

### ✅ Validaciones de Negocio
- ✅ No se puede eliminar especialidad con psicólogos asignados
- ✅ Muestra conteo de psicólogos por especialidad
- ✅ No permite crear/editar psicólogo sin especialidad
- ✅ Nombre de especialidad requerido

### ✅ Características de UX
- 📊 Badge con conteo de psicólogos por especialidad
- 🔒 Botón eliminar deshabilitado si tiene psicólogos
- ✅ Mensajes de éxito/error con alertas dismissible
- 🎨 Iconos Font Awesome para mejor visualización
- 📝 Tooltips informativos

## 🔄 Flujo de Uso

### Gestionar Especialidades
1. Admin → Navbar → "Especialidades"
2. Crear nueva especialidad (nombre + descripción)
3. Ver listado con conteo de psicólogos
4. Editar inline cualquier especialidad
5. Cambiar estado activo/inactivo
6. Eliminar (solo si count = 0)

### Asignar Especialidad a Psicólogo
1. Admin → "Psicólogos"
2. Crear nuevo → Seleccionar especialidad del dropdown
3. O editar existente → Cambiar especialidad del dropdown
4. Guardar → Validación asegura especialidad válida

## 🗃️ Estructura de Base de Datos

```sql
-- Tabla creada previamente
CREATE TABLE Especialidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo'
);

-- Tabla Psicologo (modificada)
CREATE TABLE Psicologo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_especialidad INT NOT NULL,  -- ← Cambió de 'especialidad' VARCHAR
    experiencia TEXT,
    horario TEXT,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    CONSTRAINT fk_psicologo_especialidad 
        FOREIGN KEY (id_especialidad) 
        REFERENCES Especialidad(id) 
        ON UPDATE CASCADE 
        ON DELETE RESTRICT  -- ← No permite eliminar especialidad con psicólogos
);
```

## 📊 Ejemplos de Especialidades

Algunas especialidades comunes que se pueden agregar:
- Psicología Clínica
- Psicología Infantil
- Psicología Organizacional
- Psicología Educativa
- Neuropsicología
- Terapia de Pareja
- Psicología Deportiva
- Psicología Forense

## 🚀 Acceso

**URL:** `http://localhost/CICLO8_Desarrollo_Web_Multiplataforma/Parcial2_MM_EZ_PV/index.php?url=especialidad`

**Requisitos:**
- Usuario autenticado como Admin
- Navbar muestra el enlace "Especialidades" con icono de graduación

## ✅ Testing Recomendado

1. **Crear especialidad** → Verificar que aparece en listado
2. **Crear psicólogo** → Verificar que aparece en dropdown de especialidades
3. **Asignar especialidad** → Verificar que se guarda correctamente
4. **Intentar eliminar especialidad con psicólogos** → Debe mostrar error
5. **Desactivar especialidad** → Verificar que no aparece en dropdown de crear
6. **Editar especialidad** → Verificar que se actualiza en todos lados

## 🎉 Resultado Final

Sistema completo y funcional que:
- ✅ Gestiona especialidades de forma independiente
- ✅ Integra especialidades con psicólogos
- ✅ Mantiene integridad referencial
- ✅ Previene eliminación accidental
- ✅ Proporciona UX clara e intuitiva
