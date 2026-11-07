---
name: code-review-estricto
description: Utilizar cuando el usuario solicite una revisión de código estricta, auditoría de código limpio, análisis de vulnerabilidades de seguridad o detección de antipatrones.
---

# Guía de Revisión Estricta de Código

Cuando esta skill sea activada, analiza el código provisto aplicando los siguientes criterios:

1. Seguridad y Robustez:
   - Validación estricta de parámetros de entrada.
   - Manejo explícito de excepciones (evitar try/catch vacíos).
   - Prevención de fugas de datos y saneamiento de consultas.

2. Principios SOLID y Clean Code:
   - Alta cohesión en funciones (única responsabilidad).
   - Nombres semánticos y descriptivos para variables y funciones.
   - Reducción de complejidad ciclomática.

3. Antipatrones:
   - Identificación de código duplicado (DRY).
   - Eliminación de variables globales o estado mutable innecesario.

## Estructura de Respuesta Obligatoria

Toda respuesta debe estructurarse con la siguiente plantilla:

- **Dictamen:** [Apto para producción | Requiere refactorización]
- **Vulnerabilidades / Hallazgos críticos:** (Listar con nivel de severidad: Alta, Media, Baja)
- **Propuesta de Mejora:** Fragmento de código refactorizado y corregido.

Nota: Toda la evaluación, explicaciones y sugerencias deben ser redactadas obligatoriamente en español.