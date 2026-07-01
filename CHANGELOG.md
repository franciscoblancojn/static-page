# Changelog

> Todas las versiones del plugin Static Page.

---

## [1.10.0] — 2025

- Añadida funcionalidad **Secciones Globales** — permite extraer fragmentos HTML (headers, footers, etc.) de cualquier página, guardarlos como secciones globales y reemplazarlos automáticamente al servir páginas estáticas.

## [1.9.2] — 2025

- Fix: selector de páginas en la tabla ahora agrupa por padre con colapso
- Mejora en el renderizado de páginas con subpáginas

## [1.9.1] — 2025

- Fix: filtros en la tabla de páginas estáticas
- Añadidos indicadores de páginas activas y con archivo

## [1.9.0] — 2025

- Nueva interfaz admin con pestañas (Páginas Estáticas, Configuración)
- Tabla de páginas con búsqueda, filtros y acciones masivas
- Acciones por página: Activar/Desactivar, Regenerar, Eliminar, Ver Original
- Mejora en el sistema de regeneración vía AJAX

## [1.8.0] — 2025

- Integración con Elementor: auto-regeneración al guardar
- Mejora en la combinación de CSS externo (agrupado por plugin/theme)
- Añadido purge de CSS no utilizado

## [1.7.0] — 2025

- Añadido procesamiento de JS externo/interno (Beta)
- Añadida generación de archivos CSS/JS externos separados
- Mejora en el minificado de CSS y HTML

## [1.6.0] — 2025

- Añadido soporte para ignorar CSS/JS específicos
- Mejora en el manejo de `@import` en CSS
- Reemplazo de `<a tabindex="0">` por `<span>`

## [1.5.0] — 2025

- Añadido sistema de configuración global (directorio de salida, auto-regenerar)
- Múltiples tipos de organización de archivos (plano, por slug, por ID en grupos, por padre)

## [1.4.0] — 2025

- REST API para guardado de config y HTML
- Meta box en editor de páginas
- Procesamiento HTML cliente-side (minificado, inlining CSS, eliminación admin bar)

## [1.3.0] — 2025

- Auto-update vía GitHub
- Sistema de logs

## [1.2.0] — 2025

- Mejoras en la generación de páginas estáticas
- Soporte para Elementor

## [1.1.0] — 2025

- Primera versión estable
- Generación y servido de páginas estáticas
- Inlining de CSS
- Minificado de HTML
