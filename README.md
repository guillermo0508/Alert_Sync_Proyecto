# Alert Sync Proyecto

## Objetivo
Utilizar GitHub como herramienta colaborativa para almacenar, organizar y llevar el control de las diferentes versiones del proyecto Alert Sync.

## Descripción general
Sistema de seguridad personal compuesto por tres aplicaciones que comparten el mismo backend:

| Aplicación | Ubicación | Tecnología |
|---|---|---|
| **Web** | `frontend-vue/` | Vue 3 + Vite |
| **Backend API** | `backend/` | Laravel 11 + PostgreSQL |
| **App Móvil** | `~/Documents/alertsync_app_movil/` | Android (Kotlin + Compose) |
| **Smartwatch** | `~/Documents/alertsync-smartwatch/` | Wear OS (Kotlin + Compose) |

---

## Inicio rápido en desarrollo local

### 1. Backend (Laravel)

```sh
cd backend
composer install
cp .env.example .env      # solo si aún no tienes .env
php artisan key:generate
php artisan migrate --force

# Arrancar — usar 0.0.0.0 para que el móvil y el reloj físicos puedan conectarse
php artisan serve --host=0.0.0.0 --port=8000
```

### 2. Frontend Web (Vue)

```sh
cd frontend-vue
npm install
npm run dev               # disponible en http://localhost:5173
```

El proxy de Vite reenvía automáticamente `/api` a `http://localhost:8000/api`.

### 3. App Móvil y Smartwatch (Android Studio)

Abre cada proyecto por separado en Android Studio:
- `~/Documents/alertsync_app_movil/`
- `~/Documents/alertsync-smartwatch/`

**Importante — IP local:** Si tu IP local cambió, actualiza `BASE_URL` en:
- `alertsync_app_movil/.../data/network/ApiConfig.kt`
- `alertsync-smartwatch/.../data/network/ApiConfig.kt`

Para obtener tu IP: `ifconfig | grep "inet " | grep -v 127.0.0.1`

Nunca uses `localhost` en un dispositivo físico; apuntaría al propio dispositivo.

---

## Base de datos

El backend usa PostgreSQL. Las credenciales solo van en el servidor (nunca en las apps cliente).

1. Crea la base `alertsync_laravel` y un usuario con permisos.
2. Copia `backend/.env.example` a `backend/.env` y configura `DB_*`.
3. Ejecuta `php artisan migrate --force`.

---

## Referencia de endpoints de la API

Base URL: `http://<IP>:8000/api` (desarrollo) o `https://<dominio>/api` (producción).

### Autenticación pública

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/auth/register` | Crear cuenta |
| POST | `/auth/login` | Iniciar sesión (web y móvil) |
| POST | `/auth/watch-login` | Iniciar sesión en el reloj con código de 6 dígitos |

### Autenticación protegida (`Authorization: Bearer <token>`)

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/auth/me` | Perfil del usuario actual |
| POST | `/auth/logout` | Cerrar sesión |
| GET | `/auth/watch-code` | Obtener código de vinculación para el reloj |
| POST | `/auth/watch-code/regenerate` | Regenerar código de vinculación |

### Alertas SOS

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | `/alerts/sos` | Disparar alerta SOS — body: `{ source, latitude?, longitude?, message? }` |
| GET | `/alerts` | Historial de alertas |

Valores válidos de `source`: `mobile`, `watch`, `web`, `alexa`, `dashboard`, `simulator`

La respuesta de POST `/alerts/sos` tiene la forma:
```json
{
  "message": "Alerta SOS enviada correctamente.",
  "alert": { "id": 1, "source": "mobile", "status": "sent", ... },
  "contacts_count": 2
}
```

### Contactos

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | `/contacts` | Lista de contactos de emergencia |
| POST | `/contacts` | Agregar contacto |
| DELETE | `/contacts/{id}` | Eliminar contacto |

---

## Flujo de vinculación Smartwatch

```
Web / App Móvil          Backend              Smartwatch (Wear OS)
       │                    │                        │
       │─ GET /auth/watch-code ─────────────────────►│ (no; la web la pide)
       │◄─ { code: "123456", expires_in_seconds } ───│
       │                    │                        │
       │ (muestra el código al usuario)               │
       │                    │◄── POST /auth/watch-login ──│
       │                    │      { code: "123456" }     │
       │                    │──► { token, user } ─────────│
       │                    │                        │
       │                    │◄── POST /alerts/sos ───│
       │                    │  { source: "watch" }   │
```

---

## Integrantes del equipo
- Guillermo Amescua (Líder)
- Gustavo Borrayo
- Alan Hernandez
