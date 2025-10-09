/**
 * Script para hacer las tablas responsive automáticamente
 */
document.addEventListener('DOMContentLoaded', function() {
    // Encontrar todas las tablas que no estén ya en un contenedor responsive
    const tables = document.querySelectorAll('table:not(.table-responsive table)');
    
    tables.forEach(function(table) {
        // Verificar si la tabla ya está dentro de un .table-responsive
        if (!table.closest('.table-responsive')) {
            // Crear el wrapper responsive
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            
            // Insertar el wrapper antes de la tabla
            table.parentNode.insertBefore(wrapper, table);
            
            // Mover la tabla dentro del wrapper
            wrapper.appendChild(table);
        }
    });
});
