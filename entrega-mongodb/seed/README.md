# Archivos de carga inicial (seed data)

Estos archivos representan los datos base con los que el sistema ALERTSYNC arranca la primera vez, antes de que existan usuarios, contactos o alertas reales.

- **`plans.seed.json`** — catálogo inicial de planes (Básico y Premium): precio, contactos máximos permitidos y lista de características. Esta es la única colección del proyecto que realmente necesita datos precargados, ya que el resto de las colecciones (`users`, `contacts`, `alerts`, `payments`) se llenan de forma natural conforme las personas usan la aplicación.
- **`site_content.seed.json`** — contenido editable de la página pública (textos del hero, características, preguntas frecuentes), con los valores de fábrica del sitio.

## Cómo cargarlos en MongoDB

```bash
mongoimport --uri="<tu-cadena-de-conexion-atlas>" --collection=plans --jsonArray --file=plans.seed.json
mongoimport --uri="<tu-cadena-de-conexion-atlas>" --collection=site_content --jsonArray --file=site_content.seed.json
```

En la práctica, la aplicación no depende de que estos documentos existan de antemano: si `plans` o `site_content` están vacíos, el backend (`Plan::getOrDefault()` / `SiteContentController`) sirve estos mismos valores por defecto desde el código en tiempo de ejecución, y solo los guarda en la base de datos hasta que un administrador los edita por primera vez desde el panel. Esto evita tener una base de datos "a medio llenar" con configuración parcial.
