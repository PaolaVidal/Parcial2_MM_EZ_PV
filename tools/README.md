# Herramienta de Regeneración de QRs

## Problema Detectado

Los QRs de tickets se estaban generando con:
- ❌ Nombre incorrecto: `ticket_1_png.png` (duplicación de `.png`)
- ❌ Posible contenido incorrecto

## Solución

### 1. Ejecutar script de regeneración

Abre en tu navegador:
```
http://localhost/CICLO8_Desarrollo_Web_Multiplataforma/Parcial2_MM_EZ_PV/tools/regenerar_qr_tickets.php
```

El script automáticamente:
- ✅ Regenera todos los QRs de tickets
- ✅ Corrige el formato del nombre: `ticket_1.png`
- ✅ Asegura contenido correcto: `PAGO:ID`
- ✅ Actualiza la base de datos
- ✅ Limpia archivos antiguos incorrectos

### 2. Verificar resultados

Después de ejecutar, verás un resumen con:
- Tickets regenerados exitosamente
- Errores (si los hay)
- Archivos antiguos eliminados

### 3. Probar scanner

1. Ir a `/ticket` (menú psicólogo)
2. Clic "Escanear Ticket"
3. Escanear cualquier QR regenerado
4. Debe detectar correctamente

## Formato Correcto de QRs

### Tickets:
- **Contenido:** `PAGO:2` (donde 2 es el ID del pago)
- **Archivo:** `qrcodes/ticket_2.png`

### Citas:
- **Contenido:** `CITA:5` (donde 5 es el ID de la cita)
- **Archivo:** `qrcodes/cita_id_5.png`

## Corrección Aplicada

En `Controllers/PsicologoController.php` línea 329:

**ANTES:**
```php
$qrRuta = QRHelper::generarQR('PAGO:'.$idPago,'ticket','ticket_'.$idPago.'.png');
// ❌ Nombre con .png duplicado
```

**AHORA:**
```php
$qrRuta = QRHelper::generarQR('PAGO:'.$idPago,'ticket','ticket_'.$idPago);
// ✅ Nombre correcto, QRHelper agrega .png
```

## Debugging

Si el scanner sigue sin detectar:

1. Abrir consola del navegador (F12)
2. Ver mensajes de console.log
3. Verificar que dice: "QR detectado: PAGO:2"
4. Si dice "CITA:X" es porque estás escaneando QR de cita
5. Si no detecta nada, verificar que la cámara esté activa

## Eliminar archivos antiguos manualmente

Si prefieres hacerlo manualmente:

```powershell
# Ir a la carpeta del proyecto
cd C:\xampp\htdocs\CICLO8_Desarrollo_Web_Multiplataforma\Parcial2_MM_EZ_PV

# Eliminar archivos con formato incorrecto
Remove-Item public\qrcodes\ticket_*_png.png
```
