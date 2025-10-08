# Configurar HTTPS en XAMPP para Escaneo QR desde Móvil

## Problema
Los navegadores móviles requieren HTTPS para acceder a la cámara. 
Error: "Camera access is only supported in secure context like https or localhost"

## Solución 1: HTTPS con mkcert (MÁS FÁCIL)

### Paso 1: Instalar mkcert
1. Descarga mkcert desde: https://github.com/FiloSottile/mkcert/releases
2. Descarga `mkcert-v1.4.4-windows-amd64.exe`
3. Renómbralo a `mkcert.exe`
4. Muévelo a `C:\mkcert\`

### Paso 2: Instalar CA local
Abre PowerShell como Administrador:
```powershell
cd C:\mkcert
.\mkcert.exe -install
```

### Paso 3: Crear certificado para tu IP
```powershell
.\mkcert.exe localhost 127.0.0.1 172.20.10.2 ::1
```

Esto genera:
- `localhost+3.pem` (certificado)
- `localhost+3-key.pem` (clave privada)

### Paso 4: Copiar certificados a Apache
```powershell
copy localhost+3.pem C:\xampp\apache\conf\ssl.crt\server.crt
copy localhost+3-key.pem C:\xampp\apache\conf\ssl.key\server.key
```

### Paso 5: Habilitar SSL en XAMPP
1. Edita `C:\xampp\apache\conf\extra\httpd-ssl.conf`
2. Busca estas líneas y actualiza:
```apache
SSLCertificateFile "conf/ssl.crt/server.crt"
SSLCertificateKeyFile "conf/ssl.key/server.key"
```

3. Edita `C:\xampp\apache\conf\httpd.conf`
4. Descomenta (quita el #):
```apache
LoadModule ssl_module modules/mod_ssl.so
Include conf/extra/httpd-ssl.conf
```

### Paso 6: Reiniciar Apache
En XAMPP Control Panel:
- Stop Apache
- Start Apache

### Paso 7: Instalar certificado en móvil
1. Conecta tu móvil a la misma red WiFi
2. En el móvil, abre Chrome
3. Ve a: `http://172.20.10.2/CICLO8_Desarrollo_Web_Multiplataforma/Parcial2_MM_EZ_PV/tools/rootCA.pem`
4. Descarga y instala el certificado
5. Android: Configuración > Seguridad > Credenciales cifradas > Instalar desde almacenamiento
6. iOS: Ajustes > General > Perfil > Instalar perfil

### Paso 8: Acceder con HTTPS
Ahora accede desde el móvil:
```
https://172.20.10.2/CICLO8_Desarrollo_Web_Multiplataforma/Parcial2_MM_EZ_PV/
```

---

## Solución 2: Usar ngrok (Más rápido, temporal)

### Paso 1: Instalar ngrok
1. Descarga desde: https://ngrok.com/download
2. Extrae `ngrok.exe` en `C:\ngrok\`
3. Crea cuenta en ngrok.com y obtén tu token
4. Ejecuta:
```powershell
cd C:\ngrok
.\ngrok.exe authtoken TU_TOKEN_AQUI
```

### Paso 2: Crear túnel HTTPS
```powershell
.\ngrok.exe http 80
```

Esto te dará una URL HTTPS pública temporal como:
```
https://abc123.ngrok.io
```

### Paso 3: Acceder desde móvil
Usa la URL de ngrok en tu móvil. Funciona desde cualquier red.

**Ventajas:**
- ✅ Sin configurar certificados
- ✅ Funciona de inmediato
- ✅ Accesible desde cualquier red

**Desventajas:**
- ❌ URL cambia cada vez que reinicias ngrok
- ❌ Sesión gratuita expira después de 2 horas

---

## Solución 3: Entrada manual de código QR

Si no puedes usar HTTPS, puedes implementar entrada manual del código QR:

### Ya implementado en tu sistema:
En `Views/psicologo/scan.php` hay un campo "Entrada manual" donde puedes escribir:
```
CITA:123
```

Y hacer clic en el botón de búsqueda (lupa).

---

## Recomendación

**Para desarrollo:** Usa **ngrok** (más rápido)
**Para producción:** Configura **HTTPS con mkcert** o certificado real

## Verificar que funciona

1. Accede con HTTPS
2. El navegador debe mostrar un candado 🔒
3. Ve a Tickets > Escanear Cita
4. El navegador pedirá permiso para usar la cámara
5. Acepta y escanea el QR

---

## Troubleshooting

### Error: "NET::ERR_CERT_AUTHORITY_INVALID"
- Solución: Instala el certificado raíz en el móvil (Paso 7)

### Error: "Cámara no detectada"
- Verifica que Chrome tenga permisos de cámara
- Android: Configuración > Apps > Chrome > Permisos > Cámara
- iOS: Ajustes > Chrome > Cámara

### Apache no inicia después de configurar SSL
- Verifica que los archivos .crt y .key existan
- Revisa logs: `C:\xampp\apache\logs\error.log`
