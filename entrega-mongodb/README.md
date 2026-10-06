# ALERTSYNC — Entrega de Base de Datos (MongoDB Atlas)

Esta carpeta contiene una copia completa de la base de datos `alertsync` (MongoDB Atlas) usada por el proyecto, exportada el 24 de julio de 2026.

## Contenido

- **`dump/`** — Backup completo en formato nativo de MongoDB (`.bson` + `.metadata.json` por colección), generado con `mongodump`. Este es el respaldo "real": permite restaurar la base de datos exactamente como estaba, con todos los tipos de datos correctos.
- **`json/`** — Las mismas colecciones exportadas como archivos `.json` legibles (uno por colección), generados con `mongoexport`. Útil para revisar el contenido directamente sin necesidad de tener MongoDB instalado — se puede abrir con cualquier editor de texto.

## Colecciones incluidas

| Colección | Contenido |
|---|---|
| `users` | Cuentas de usuarios y administradores (contraseñas siempre cifradas con bcrypt, nunca en texto plano) |
| `contacts` | Contactos de emergencia registrados por cada usuario |
| `alerts` | Alertas SOS activadas |
| `payments` | Pagos/suscripciones procesados |
| `plans` | Configuración de los planes Básico y Premium (precios, características) |

**Nota:** la colección `personal_access_tokens` (tokens de sesión activos) se excluyó intencionalmente de esta entrega, ya que son credenciales de acceso temporales/revocables — no forman parte del contenido real del proyecto y no aportan nada a la evaluación.

## Cómo restaurar el backup completo (`dump/`)

Con MongoDB Database Tools instalado:

```bash
mongorestore --uri="mongodb://localhost:27017" --db=alertsync dump/alertsync
```

(o apuntando a otra base de datos/Atlas propio, cambiando el `--uri`).

## Cómo ver el contenido sin instalar nada

Simplemente abre cualquiera de los archivos en `json/` con un editor de texto o VS Code — son JSON legibles.
