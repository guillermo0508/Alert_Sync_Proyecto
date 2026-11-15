# AlertSync – servicio de correo (Vercel)

El backend Laravel está en Render (plan gratuito), que **bloquea los puertos SMTP**.
Este servicio corre gratis en Vercel (sin tarjeta) y envía los correos por **SMTP de Gmail**
(`smtp.gmail.com:587`, STARTTLS). Laravel le pasa cada correo por HTTPS (`MAIL_MAILER=relay`).

```
App iOS / Watch ──HTTPS──▶ Laravel (Render) ──HTTPS──▶ mail-relay (Vercel) ──SMTP 587──▶ Gmail ──▶ contactos
```

## 1. Contraseña de aplicación de Gmail
1. Entra a https://myaccount.google.com con `alertsync26@gmail.com`.
2. Activa **Verificación en dos pasos** (Seguridad).
3. Abre https://myaccount.google.com/apppasswords, crea una llamada "AlertSync" y copia los 16 caracteres.

## 2. Desplegar en Vercel (gratis, sin tarjeta)
1. Entra a https://vercel.com y regístrate con tu cuenta de GitHub (plan **Hobby**).
2. **Add New → Project** → importa el repositorio `Alert_Sync_Proyecto`.
3. En **Root Directory** elige `mail-relay`. Framework: **Other**.
4. En **Environment Variables** agrega:
   - `GMAIL_USER` = `alertsync26@gmail.com`
   - `GMAIL_APP_PASSWORD` = la contraseña de aplicación del paso 1
   - `RELAY_SECRET` = una clave larga (genérala con `openssl rand -hex 32`)
5. **Deploy**. Abre `https://<tu-proyecto>.vercel.app/api/send`: debe responder `{"ok":true,...}`.

## 3. Conectar Render
En Render → `alertsync-backend` → **Environment**:
- `MAIL_MAILER` = `relay`
- `MAIL_RELAY_URL` = `https://<tu-proyecto>.vercel.app/api/send`
- `MAIL_RELAY_SECRET` = la misma clave de `RELAY_SECRET`
- `MAIL_FROM_ADDRESS` = `alertsync26@gmail.com`, `MAIL_FROM_NAME` = `Alert Sync`
- `LOG_CHANNEL` = `stderr` (para ver errores de correo en la pestaña **Logs**)

Guarda; Render vuelve a desplegar solo.
