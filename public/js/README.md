# Librería html5-qrcode

Este directorio contiene la librería **html5-qrcode** descargada localmente para que el sistema funcione **sin depender de conexión a internet** o CDNs externos.

## Archivo actual:
- `html5-qrcode.min.js` (v2.3.8) - 375 KB

## Usado en:
- `/psicologo/scan` - Escanear citas QR
- `/psicologo/citas` - Modal scanner de citas
- `/ticket` - Scanner de tickets de pago (psicólogo)

## Actualizar librería:
Si necesitas actualizar a una versión más reciente:

```powershell
# Descargar última versión
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/html5-qrcode@latest/html5-qrcode.min.js" -OutFile "public\js\html5-qrcode.min.js"
```

## Información:
- **Repositorio:** https://github.com/mebjas/html5-qrcode
- **Documentación:** https://scanapp.org/html5-qrcode-docs/
- **Licencia:** Apache-2.0

---
*Archivo descargado el: 06/10/2025*
