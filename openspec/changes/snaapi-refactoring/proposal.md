# Proposal: SNAAPI Full Refactoring

## Request Analysis

**Original Request**: Refactoring completo de la API REST SNAAPI para mejorar escalabilidad y mantenibilidad.
**Request Type**: refactor
**Affected Areas**: Orchestrators, DataTransformers, Infrastructure, DependencyInjection, EventSubscribers, Tests
**Confidence Level**: 95%

---

## Problem Statement

### What We're Doing

Refactoring integral de SNAAPI (Symfony 6.4 API Gateway) que agrega contenido editorial de múltiples microservicios. El objetivo es descomponer God Objects, eliminar duplicación, mejorar la adherencia a SOLID, e incrementar la escalabilidad y testabilidad del sistema.

### Why It's Needed

1. **God Object `EditorialOrchestrator`**: 536 lineas, 16+ dependencias en constructor, metodo `execute()` de 179 lineas. Extremadamente dificil de testear, modificar y mantener.
2. **Duplicacion de codigo critica**: Logica de procesamiento de `insertedNews` y `recommendedEditorials` es copy-paste con minimas diferencias.
3. **Handlers duplicados**: `BodyElementDataTransformerHandler`, `MediaDataTransformerHandler`, y `MultimediaOrchestratorHandler` comparten patron identico sin abstraccion comun.
4. **Traits como mixins**: `MultimediaTrait` y `UrlGeneratorTrait` violan composicion, esconden dependencias y son dificiles de testear.
5. **Acoplamiento fuerte**: Orquestador acoplado directamente a 7 query clients concretos sin abstracciones intermedias.
6. **Problemas N+1**: Queries secuenciales por firma/signature sin batch fetching.
7. **Async bloqueante**: Promises creadas pero resueltas sincrónicamente con `->wait()`.
8. **Tests fragiles**: Test del orquestador de 1700 lineas con metodos de 233 lineas y 15+ mocks.

### Who Benefits

- **Equipo de desarrollo**: Menor costo de mantenimiento, mayor velocidad de desarrollo
- **Usuarios finales**: Mejor rendimiento (resolucion de N+1 y async real)
- **Operaciones**: Mayor resiliencia ante fallos de servicios externos

### Constraints

- **Technical**: Symfony 6.4, PHP 8.1+, arquitectura DDD existente, clientes externos como paquetes composer
- **Business**: Mantener compatibilidad con apps moviles consumidoras
- **Testing**: PHPStan Level 9, MSI 79%, PSR-12 + Symfony coding standards

### Success Criteria

1. `EditorialOrchestrator` reducido a <100 lineas con <=5 dependencias directas
2. Zero duplicacion en procesamiento de editorial groups (inserted/recommended)
3. Handlers unificados bajo abstraccion generica `ChainHandler`
4. Traits eliminados, reemplazados por servicios inyectables
5. Interfaces de repositorio para todos los query clients
6. Tests del orquestador divididos en tests focalizados (<200 lineas cada uno)
7. Todos los tests existentes siguen pasando (green suite)
8. PHPStan Level 9 sin errores nuevos
