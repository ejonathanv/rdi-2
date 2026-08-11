# Roadmap de mejoras — RDI

Documento vivo: recomendaciones para hacer el sistema más robusto y aportar más valor.
Conforme se implemente cada ítem, márcalo con `[x]` y, si aplica, añade una nota breve (PR, commit o fecha).

**Cómo usarlo**
- Prioridad 1 → primero; luego 2, etc.
- Un ítem puede dividirse en subtareas al empezar la implementación.
- Al completar algo relevante, actualizar también [`PROYECTO.md`](PROYECTO.md) en “Dominio actual” / “Dominio futuro”.

---

## Estado actual (baseline)

Ya existe y no debe reimplementarse:

- [x] Rondines con QR, cuestionarios y “sin novedad”
- [x] Fotos en visitas / incidencias
- [x] Urgente de punto + notificación Twilio (`round_contact`)
- [x] Incidencias con OpenAI (limpieza + categoría / categoría nueva)
- [x] Fallback: categoría sin contactos → contactos del área
- [x] Botón de pánico + notificación a contactos del área
- [x] Panel admin: KPIs, rondines, incidencias, categorías
- [x] Confirmaciones en acciones críticas (scan, incidencia, pánico)

---

## Prioridad 1 — Cerrar el ciclo de valor

### 1. Ciclo de vida de la incidencia

Hoy se registra y se notifica; falta gestionar la respuesta.

- [x] Estados: `nueva` → `en_atencion` → `resuelta` / `descartada`
- [x] Quién tomó el caso (`assigned_to` / `acknowledged_by`)
- [x] Notas de cierre y timestamp de resolución
- [x] Tiempo de respuesta / tiempo de cierre (métricas en panel)
- [x] Notificar al cerrar (guardia y/o contactos, según reglas)
- [x] UI admin: cambiar estado desde detalle de incidencia
- [x] Filtros en listado por estado

**Por qué:** convierte RDI de “buzón de alertas” en sistema de gestión.

---

## Prioridad 2 — Producción confiable (Twilio)

### 2. Plantillas WhatsApp + colas + entrega

- [ ] Plantillas Meta/Twilio para: pánico, incidencia, punto urgente
- [ ] Envío por Content API / template (no solo `Body` libre)
- [ ] Jobs en cola (`ShouldQueue`) para notificaciones
- [ ] Reintentos ante fallos de Twilio
- [ ] Log de entrega por contacto (aceptado / fallido / undelivered)
- [ ] Configuración de SIDs de plantilla en `.env` / config

**Por qué:** en producción WhatsApp exige plantillas para mensajes iniciados por el negocio; las colas evitan perder alertas.

### 2b. Canal in-app + Web Push (puente mientras Twilio no esté listo)

- [x] Inbox in-app (campana) para todos los roles
- [x] Web Push (VAPID) con service worker
- [x] Suscripción por usuario autenticado
- [x] Alertas operativas: pánico, incidencia, punto urgente, cierre
- [x] Destinatarios: todos los usuarios del área (excepto el actor)

**Por qué:** permite recibir alertas en el navegador sin depender de WhatsApp/SMS aprobados.

---

## Prioridad 3 — Emergencias trazables

### 3. Historial admin de pánicos

- [ ] Listado admin de `panic_alerts` (área activa)
- [ ] Detalle: guardia, hora, área, recorrido ligado
- [ ] KPI existente (`panics_today`) enlazado al listado
- [ ] (Opcional) acuse de recibo por contacto

### 4. Acuse / respuesta de contactos

- [ ] Acciones tipo: recibido / voy en camino / cerrado
- [ ] Visible en detalle de incidente, urgente o pánico
- [ ] Canal: link firmado en WhatsApp o panel de contacto (ver §5)

**Por qué:** saber si alguien reaccionó a la emergencia.

---

## Prioridad 4 — Cumplimiento y gerencia

### 5. Reportes agregados

Menú **Reportes** (submenu) — primer trio orientado a detección y resolución de incidencias:

- [x] **Volumen de incidencias** — totales del periodo (abiertas / resueltas / descartadas / urgentes), desglose por categoría, serie temporal; filtros desde/hasta
- [x] **Tiempos de atención** — promedio/mediana a toma (`acknowledged_at`) y a cierre (`resolved_at`); urgente vs no; por categoría
- [x] **Puntos críticos** — ranking de checkpoints/recorridos por incidencias + visitas urgentes en el periodo
- [ ] Cumplimiento de rondines (4.º reporte futuro): % puntos visitados, duración, puntos omitidos
- [ ] Export PDF y/o Excel
- [x] Filtros por rango de fechas y área (comunes a los reportes; área = activa)

**Por qué:** es lo que suelen pedir en reuniones de planta y auditorías.

### 6. Turnos / asignación de recorridos (`round_assignments`)

- [ ] Modelo: recorrido + guardia + ventana horaria / día
- [ ] Panel: “pendientes de hoy”
- [ ] Alerta si no se inicia el recorrido a tiempo
- [ ] (Opcional) notificar al contacto/admin

*Relacionado con “dominio futuro” en PROYECTO.md.*

---

## Prioridad 5 — Escala de uso diario

### 7. Panel / experiencia para contactos

- [ ] Home de contacto (rol `contact`): urgentes, incidencias, pánicos relevantes
- [ ] Sin acceso al panel admin completo
- [ ] Marcar acuse / avance / cierre desde ahí
- [ ] Scope estricto por área y asignaciones (recorrido / categoría)

*Relacionado con “Panel de contactos” en PROYECTO.md.*

### 8. PWA / modo offline del guardia

- [ ] Manifest + service worker (PWA instalable)
- [ ] Encolar acciones sin red (scan, sin novedad, borrador de incidencia)
- [ ] Sincronizar al recuperar conexión
- [ ] Indicador claro de “pendiente de sync”

**Por qué:** en planta a menudo hay mala señal.

---

## Prioridad 6 — Diferenciadores de seguridad

### 9. GPS opcional en pánico e incidencias

- [ ] Solicitar permiso de geolocalización en el cliente
- [ ] Guardar lat/lng (y precisión) en el registro
- [ ] Incluir enlace a mapa en el mensaje Twilio
- [ ] Política de privacidad / consentimiento visible

### 10. Abandonar / reanudar patrulla

- [ ] Acción “abandonar recorrido” con motivo
- [ ] Estado claro de puntos no visitados
- [ ] Reanudar o cerrar como incompleto
- [ ] Reflejo en reportes de cumplimiento

*Relacionado con “Abandonar patrulla a mitad” en PROYECTO.md.*

### 11. SLA y escalamiento

- [ ] Reglas: si urgente/pánico no se reconoce en X minutos → segundo nivel
- [ ] Grupo de escalamiento configurable por área
- [ ] Registro de escalamientos enviados
- [ ] Configuración admin (minutos, destinatarios)

---

## Prioridad 7 — Inteligencia y gobierno

### 12. Resumen diario / semanal

- [x] Digest automático por email a **contactos** del área:
  - Urgentes pendientes (diario 08:00 CDMX) — incidencias urgentes abiertas + visitas urgentes sin atender
  - Resumen semanal de incidencias (viernes 13:00 CDMX)
  - Recorridos del día (diario 20:00 CDMX)
- [x] Visitas urgentes: acción “Marcar urgente como atendido” en detalle de patrulla
- [ ] Canal WhatsApp (además de email)
- [ ] Preferir agregación sin IA; OpenAI solo si aporta redacción

**Comandos:** `reports:send-open-urgents-digest`, `reports:send-weekly-incidents-digest`, `reports:send-daily-patrols-digest`

### 13. Detección de patrones

- [ ] Alertar puntos con muchos urgentes/incidencias en N días
- [ ] Sugerir revisar instrucciones o cuestionario del punto
- [ ] Widget o sección en el dashboard admin

### 14. Categorías nuevas creadas por IA

- [ ] Aviso al admin: “categoría nueva sin contactos dedicados”
- [ ] (Opcional) sugerir contactos por defecto del área
- [ ] Badge en listado de categorías (“creada por IA” / “sin contactos”)

### 15. Auditoría

- [ ] Log de cambios críticos: contactos de categoría/recorrido, cierre de incidencia, desactivar recorrido
- [ ] Quién / cuándo / qué cambió
- [ ] Consulta admin (solo lectura)

### 16. Prueba de alertas

- [ ] Botón admin “enviar prueba” (pánico o incidencia de prueba)
- [ ] No crea emergencia real (flag `is_test` o canal de prueba)
- [ ] Útil para validar Twilio y números de contactos

### 17. Backups y fotos a escala

- [ ] Backup diario de base de datos
- [ ] Storage objeto (S3/R2) si hay varios servidores o mucho volumen
- [ ] Política de retención de fotos
- [ ] Monitoreo de disco / fallos de escritura

---

## Orden sugerido (resumen)

| # | Tema | Prioridad |
|---|------|-----------|
| 1 | Estados + cierre de incidencias | 1 |
| 2 | Cola + plantillas Twilio | 2 |
| 3 | Historial de pánicos + acuse | 3 |
| 4 | Reportes de cumplimiento | 4 |
| 5 | Panel contacto / PWA offline | 5 |
| 6 | GPS pánico + abandonar patrulla + SLA | 6 |
| 7 | Digests, patrones, auditoría, pruebas, backups | 7 |

---

## Notas de implementación

- Mantener idioma de UI en español.
- Todo scoped por `area_id` / área activa.
- Cada feature nueva: tests Feature + Pint + nota en este archivo y en `PROYECTO.md`.
- Costos variables (Twilio/OpenAI): preferir WhatsApp utility, `gpt-4o-mini`, y no over-notify.

---

## Historial de avances

| Fecha | Ítem | Notas |
|-------|------|-------|
| 2026-08-11 | §12 Digests email a contactos | Urgentes pendientes, semanal incidencias, recorridos del día + cierre de visita urgente |
| 2026-08-11 | §5 Reportes agregados | Trio implementado: Volumen, Tiempos de atención, Puntos críticos + menú submenu |
| 2026-08-11 | §5 Reportes agregados (definición) | Trio de menú: Volumen de incidencias, Tiempos de atención, Puntos críticos; cumplimiento de rondines queda como 4.º |
| 2026-08-07 | §1 Ciclo de vida de incidencias | Estados, toma/cierre admin, filtros, KPIs tiempos, notificación al cerrar |
