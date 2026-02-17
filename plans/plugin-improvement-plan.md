# Plan de Mejora del Plugin Multi-Agent Workflow v3.1.0

**Fecha**: 2026-02-15
**Estado**: PENDIENTE
**Branch**: `claude/plugin-improvement-plan-qZo70`
**Alcance**: 22+ archivos, 14 fases, 3 niveles de prioridad
**Fuentes**: Análisis interno del codebase + Revisión de mejores prácticas 2025-2026

---

## Diagnóstico General

### Lo que funciona bien
- El flujo ROUTE → PLAN → WORK → REVIEW → COMPOUND es sólido conceptualmente
- La elevación de SOLID al diseño (Phase 3) es correcta
- El modelo de Context Activation (Fowler) está bien articulado
- Compound Engineering como filosofía es innovador
- Los principios Karpathy están bien documentados
- La transición a SOLID contextual (sin scores numéricos) ya está iniciada
- Planificación primero (80/20) — alineado con Addy Osmani
- Persistencia incremental — protege contra interrupciones
- Quality Gates con iteración acotada — evita loops infinitos
- Análisis de integración — mentalidad de extensión vs. aislamiento

### Problemas detectados (por severidad)

| # | Problema | Severidad | Archivos afectados |
|---|---------|-----------|-------------------|
| 1 | Contaminación de contexto (~3500 líneas redundantes) | CRÍTICO | 8 |
| 2 | 10+ referencias rotas a archivos/skills/comandos que no existen | CRÍTICO | 6 |
| 3 | Routing contradictorio (¿quick bypasea route?) | CRÍTICO | 4 |
| 4 | Estado de workflow inconsistente (3 formatos distintos en tasks.md) | CRÍTICO | 5 |
| 5 | SOLID enforcement ambiguo (¿bloqueante o advisory?) | ALTO | 4 |
| 6 | Specs de features nunca migran a baseline | ALTO | 3 |
| 7 | plan.md sobrecargado (1365 líneas, debería ser ~600) | ALTO | 1 |
| 8 | Terminología inconsistente entre archivos | MEDIO | 6 |
| 9 | Sin rutas de error/recovery documentadas | MEDIO | 7 |
| 10 | SESSION_CONTINUITY.md referencia 5+ comandos inexistentes | MEDIO | 1 |

### Referencias rotas confirmadas

| Referencia | Archivo:Línea | Estado |
|-----------|--------------|--------|
| `core/agent-memory.md` | compound.md:230 | NO EXISTE |
| `core/rules/ddd_rules.md` | work.md:214, compound.md:304 | NO EXISTE |
| `/workflow-skill:spec-validator` | plan.md:582, plan.md:1329 | NO EXISTE |
| `/opsx:archive` | compound.md:595 | NO EXISTE |
| `/workflows:quickstart` | discover.md:1079 | DEPRECADO |
| `/workflows:checkpoint` | work.md:400 + skills/*.md | NO EXISTE (es un skill, no un command) |
| `/workflows:snapshot` | SESSION_CONTINUITY.md (20+ refs) | NO EXISTE |
| `/workflows:restore` | SESSION_CONTINUITY.md (20+ refs) | NO EXISTE |
| `/workflows:reload` | discover.md:1214 | NO EXISTE |
| `/workflows:parallel` | CAPABILITY_PROVIDERS.md (5+ refs) | NO EXISTE |

### Gaps vs. Mejores Prácticas 2025-2026

| # | Gap | Fuente | Impacto |
|---|-----|--------|---------|
| A | Sin Reflection Pattern — Quality Gates son checks estáticos, no auto-crítica | Agentic workflows (ByteByteGo, Google Cloud) | Alto |
| B | Sin Feedback Loop — proceso lineal sin retroalimentación | Microsoft Engineering, OpenAI Cookbook | Medio |
| C | Sin Chunking explícito — outputs monolíticos causan "jumbled mess" | Addy Osmani | Medio |
| D | Sin Test Contract Sketch temprano — tests solo aparecen en implementación | CodeSIM, RTADev | Alto |
| E | Sin Right-Sizing de modelo por fase | UiPath, Google Cloud | Bajo |
| F | HITL débil — solo al inicio y final, no en decisiones de alto riesgo | Parseur, Permit.io, "Human Above the Loop" | Alto |
| G | Sin Security Threat Analysis en diseño | OWASP, AWS | Medio |
| H | SOLID auto-scoring rígido — LLM se auto-asigna scores altos | LangChain State of Agent Engineering | Alto |
| I | Sin Decision Log — decisiones no trazables | Qodo, "Attribution-Based Review" | Medio |
| J | Sin Rollback/Recovery entre fases | Producción agentic (arXiv, Digital Applied) | Bajo |

---

## Fases de Implementación

### PRIORIDAD 1: Corrección y Consolidación (Fases 1-4)

---

### Fase 1: Eliminar referencias rotas

**Objetivo**: Hacer que el 100% de las referencias internas apunten a archivos/skills/comandos que existen.

**Prompt para ejecutar esta fase**:

```
Necesito que corrijas todas las referencias rotas en el plugin multi-agent-workflow.

Referencias rotas confirmadas:

1. compound.md:230 → referencia `core/agent-memory.md` que no existe
   - ACCIÓN: Eliminar la referencia o crear la sección inline si el contenido es necesario

2. work.md:214 y compound.md:304 → referencia `core/rules/ddd_rules.md` que no existe
   - ACCIÓN: Las reglas DDD están integradas en architecture-reference.md. Actualizar las referencias para apuntar ahí

3. plan.md:582 y plan.md:1329 → referencia `/workflow-skill:spec-validator` que no existe
   - ACCIÓN: Determinar si la funcionalidad de validación de specs está en otro skill (¿spec-analyzer agent?) y actualizar, o eliminar la referencia si es aspiracional

4. compound.md:595 → referencia `/opsx:archive` que no existe
   - ACCIÓN: Determinar qué hace archive y si spec-merger lo cubre. Si sí, reemplazar referencia

5. discover.md:1079 → referencia `/workflows:quickstart` deprecado
   - ACCIÓN: Cambiar a "Replaced by `/workflows:discover --setup`" con contexto claro

6. SESSION_CONTINUITY.md → 20+ referencias a `/workflows:snapshot`, `/workflows:restore`
   - ACCIÓN: Estos son skills (checkpoint, git-sync), no commands. Reescribir las secciones para usar los skill names correctos: `/multi-agent-workflow:checkpoint` y `/multi-agent-workflow:git-sync`

7. CAPABILITY_PROVIDERS.md → referencias a `/workflows:parallel`
   - ACCIÓN: Reemplazar con la referencia correcta al provider de paralelización (agent-teams o worktrees)

8. work.md:400 → `/workflows:checkpoint` debería ser skill `checkpoint`
   - ACCIÓN: Corregir a `/multi-agent-workflow:checkpoint`

9. discover.md:1214 → `/workflows:reload`
   - ACCIÓN: Eliminar o documentar como "manual: re-read CLAUDE.md"

REGLA: No crear archivos nuevos para "resolver" referencias. Si la funcionalidad no existe, eliminar la referencia limpiamente. No dejar TODOs ni "[NOT YET IMPLEMENTED]".
```

**Archivos a modificar**: compound.md, work.md, plan.md, discover.md, SESSION_CONTINUITY.md, CAPABILITY_PROVIDERS.md

---

### Fase 2: Consolidar estado de workflow (tasks.md)

**Objetivo**: Un solo formato autoritativo para tasks.md que todos los workflows respeten.

**Prompt para ejecutar esta fase**:

```
El plugin tiene 3-4 formatos diferentes para escribir tasks.md. Necesito un formato único.

PROBLEMA: Cada workflow (plan, work, review, compound) escribe secciones diferentes en tasks.md sin un contrato común. Esto rompe la reanudación entre sesiones porque el agente no sabe qué formato esperar.

SOLUCIÓN:

1. Crear `core/templates/tasks-template.md` con el formato canónico:

```markdown
# Workflow State: {feature-slug}

## Metadata
- Feature: {nombre}
- Created: {fecha}
- Last Updated: {fecha}
- Current Phase: ROUTE | SHAPE | PLAN | WORK | REVIEW | COMPOUND

## Phase Status
| Phase | Status | Updated |
|-------|--------|---------|
| Route | COMPLETED / PENDING / IN_PROGRESS | {fecha} |
| Shape | COMPLETED / SKIPPED / PENDING | {fecha} |
| Plan | COMPLETED / PENDING / IN_PROGRESS / BLOCKED | {fecha} |
| Work | COMPLETED / PENDING / IN_PROGRESS / BLOCKED | {fecha} |
| Review | APPROVED / REJECTED / PENDING / IN_PROGRESS | {fecha} |
| Compound | COMPLETED / PENDING | {fecha} |

## Tasks (generated by Plan Phase 4)
| # | Task | Status | Checkpoint |
|---|------|--------|-----------|
| 1 | {descripción} | PENDING / IN_PROGRESS / COMPLETED / BLOCKED | {commit-hash} |

## Resume Information
- Last completed task: {N}
- Next action: {descripción}
- Blocked by: {descripción o N/A}

## Decision Log (written incrementally by each phase) [NEW — Gap I]
| Decision | Alternatives Considered | Rationale | Phase |
|----------|------------------------|-----------|-------|
| {decisión} | {alternativas descartadas} | {por qué} | {fase} |

## QA Summary (written by Review)
- Code Review: PASS / FAIL / PENDING
- Security: PASS / FAIL / PENDING
- Performance: PASS / FAIL / PENDING
- Architecture: PASS / FAIL / PENDING
- SOLID: COMPLIANT / NEEDS_WORK / NON_COMPLIANT / PENDING
```

2. Actualizar estos archivos para que LEAN y ESCRIBAN usando este formato:
   - plan.md: Phase 4 escribe la sección Tasks
   - work.md: Actualiza Status de cada task + Resume Information
   - review.md: Escribe QA Summary
   - compound.md: Marca Review como APPROVED y actualiza Phase Status
   - route.md: Crea el archivo inicial con Metadata + Phase Status (todo PENDING)

3. Cada archivo debe tener un comment al inicio de su sección de escritura:
   `<!-- Format: see core/templates/tasks-template.md -->`

NO añadir secciones extras. El formato canónico es el contrato.
```

**Archivos a modificar**: route.md, plan.md, work.md, review.md, compound.md + crear tasks-template.md

---

### Fase 3: Reducir contaminación de contexto

**Objetivo**: Reducir el contenido redundante en ~50%, mejorando la eficiencia del context window.

**Prompt para ejecutar esta fase**:

```
El plugin carga demasiado contenido redundante en el context window. Necesito reducirlo sin perder funcionalidad.

PROBLEMA: ~3500 líneas redundantes entre archivos. Cada workflow repite Flow Guards, cada archivo repite BCP, plan.md tiene 1365 líneas cuando debería ser ~600.

ACCIONES:

1. FLOW GUARDS: Cada workflow (plan, work, review, compound, shape) tiene una sección "Flow Guard" casi idéntica que verifica el estado en tasks.md.
   - EXTRAER a una referencia común. En cada workflow, reemplazar el bloque Flow Guard completo por:
   ```
   ## Prerequisites
   Read `openspec/changes/{slug}/tasks.md` and verify:
   - {fase anterior} status = COMPLETED
   If not met: STOP and inform user which phase must complete first.
   ```
   - No crear un archivo separado de flow guards. Solo reducir la repetición a 3 líneas por workflow.

2. BOUNDED CORRECTION PROTOCOL: Descrito en detalle en testing-rules.md (102 líneas), luego repetido en:
   - work.md (~40 líneas de repetición)
   - framework_rules.md (~20 líneas de repetición)
   - ACCIÓN: En work.md y framework_rules.md, reducir a: "Apply Bounded Correction Protocol (see core/rules/testing-rules.md): 3 deviation types, scale-adaptive limits, diagnostic escalation after 3 consecutive same errors."

3. PLAN.MD (1365 líneas → objetivo ~700):
   - Los ejemplos detallados de output de cada Phase ocupan ~400 líneas
   - ACCIÓN: Mantener la estructura Phase 1-4 con instrucciones claras
   - ELIMINAR: Los bloques de ejemplo de output extensos (el agente puede generar el formato correcto con instrucciones concisas)
   - ELIMINAR: Las repeticiones de "Write-Then-Advance" que se mencionan 5+ veces
   - MANTENER: Quality Gates (son críticos), Phase transitions, SOLID integration points

4. CONTEXT_ENGINEERING.md vs framework_rules.md:
   - CONTEXT_ENGINEERING.md explica el Queen Agent pattern que ya está en route.md
   - ACCIÓN: CONTEXT_ENGINEERING.md es documento de referencia (context: fork, LLM-determined load), no operativo. Verificar que no repite contenido operativo de framework_rules.md

5. SESSION_CONTINUITY.md (570 líneas):
   - 60% es sobre comandos /snapshot, /restore que no existen
   - ACCIÓN: Reescribir enfocándose en lo que SÍ funciona: checkpoint skill + git-sync skill + tasks.md como estado persistente. Objetivo: ~200 líneas.

REGLA: No mover contenido a nuevos archivos (eso solo desplaza el problema). REDUCIR eliminando duplicación. Si algo se dice en un archivo autoritativo, los demás solo referencian.
```

**Archivos a modificar**: plan.md, work.md, framework_rules.md, SESSION_CONTINUITY.md, CONTEXT_ENGINEERING.md, shape.md, review.md, compound.md

---

### Fase 4: Clarificar routing y decisiones de flujo

**Objetivo**: Eliminar ambigüedad sobre qué es obligatorio vs opcional.

**Prompt para ejecutar esta fase**:

```
El router tiene contradicciones sobre qué flujos son obligatorios y cómo interactúan.

PROBLEMAS:
1. route.md dice "Every request starts with routing. No exceptions." pero plan.md línea 14-27 sugiere que planner puede ejecutar sin routing (--workflow=task-breakdown)
2. quick.md dice "redirect to full workflow if scope grows" pero no define punto de re-entrada
3. discover.md sugiere --setup como primer paso pero no menciona que route es mandatory

ACCIONES:

1. En route.md, añadir diagrama de decisión claro:
   ```
   Toda solicitud → /workflows:route
                         ↓
                    ¿Es setup/onboarding?
                    SÍ → /workflows:discover --setup → FIN
                    NO ↓
                    ¿Es simple (≤3 archivos, sin arquitectura)?
                    SÍ → /workflows:quick → FIN
                    NO ↓
                    ¿El problema está claro?
                    SÍ → /workflows:plan
                    NO → /workflows:shape → /workflows:plan
   ```

2. En plan.md, ELIMINAR cualquier referencia a ejecución sin routing previo. La flag --workflow=task-breakdown, si existe, debe ser invocada DESDE route.

3. En quick.md, definir re-entrada explícita:
   "Si durante quick se detecta que el cambio afecta >3 archivos o requiere diseño arquitectónico:
   STOP quick. Informar al usuario: 'This change requires full planning. Run /workflows:plan to continue.'
   El estado se preserva: lo descubierto en quick se traslada como input para plan Phase 1."

4. En discover.md, aclarar:
   "/workflows:discover --setup es un comando de ONBOARDING, no de workflow. No requiere routing previo.
   Se ejecuta UNA VEZ al inicio del proyecto para generar architecture-profile.yaml.
   Después de setup, todo request va por /workflows:route."

5. Verificar que help.md refleja este flujo de decisión actualizado.

6. [NEW — Gap F: HITL entre fases] Añadir checkpoints Human-in-the-Loop obligatorios:
   - Entre Phase 2 y Phase 3 de plan.md: "¿Las specs capturan lo que quieres?"
   - Entre Phase 3 y Phase 4 de plan.md: "¿El diseño técnico te parece correcto?"
   - En plan.md, cada transición Phase N → Phase N+1 debe incluir:
     "Present summary to user. If user has corrections → apply and re-validate Quality Gate. If user approves → advance."
   - Esto evita que el plan avance 4 fases para descubrir al final que las specs estaban mal.
```

**Archivos a modificar**: route.md, plan.md, quick.md, discover.md, help.md

---

### PRIORIDAD 2: Coherencia y Calidad (Fases 5-7)

---

### Fase 5: Unificar enforcement SOLID

**Objetivo**: Una sola definición de cuándo SOLID bloquea vs cuándo es advisory.

**Prompt para ejecutar esta fase**:

```
SOLID compliance se trata de forma diferente en plan.md, work.md y review.md. Necesito una definición única.

PROBLEMA:
- plan.md Phase 3: "NON_COMPLIANT blocks" (el diseño no avanza)
- work.md Step 5: "NEEDS_WORK triggers refactor" (no bloquea, solo corrige)
- review.md: "verifica que el diseño se implementó" (no re-evalúa SOLID)
- compound.md: actualiza profile pero sin re-verificación SOLID

SOLUCIÓN: Definir la SOLID Verdict Matrix en architecture-reference.md (sección nueva) y referenciar desde los demás:

```
## SOLID Verdict Matrix

| Veredicto | En Plan (diseño) | En Work (código) | En Review (QA) |
|-----------|-----------------|-----------------|----------------|
| COMPLIANT | Proceder a Phase 4 | Proceder al checkpoint | APPROVED |
| NEEDS_WORK (relevance=critical/high) | Volver a Phase 3.2 | BCP correction loop | REJECTED con feedback |
| NEEDS_WORK (relevance=medium) | Documentar excepción, proceder | Documentar, proceder | APPROVED con notas |
| NON_COMPLIANT | BLOCKER: rediseñar | BLOCKER: no pasa checkpoint | REJECTED |
| N/A (principio no relevante) | Skip | Skip | Skip |
```

ACCIONES:
1. Añadir esta tabla a architecture-reference.md
2. En plan.md Phase 3.4: "Apply SOLID Verdict Matrix (see architecture-reference.md)"
3. En work.md Step 5/7: "Apply SOLID Verdict Matrix for Work column"
4. En review.md Phase 4: "Apply SOLID Verdict Matrix for Review column"
5. En solid-analyzer skill: emitir veredicto que incluya relevance level para que el consumidor pueda aplicar la matrix

6. [NEW — Gap H: SOLID Justification vs auto-score]
   Cambiar de auto-scoring numérico a "SOLID Justification":
   - El agente debe JUSTIFICAR textualmente cada principio aplicado con referencia a código/archivos concretos
   - Formato requerido por principio: "SRP: {archivo} tiene responsabilidad única porque {razón}. Evidencia: {referencia a código}"
   - El score numérico puede mantenerse como RESUMEN, pero la justificación textual es lo que realmente importa
   - El usuario o un segundo agente valida las justificaciones — no el mismo agente que las escribió
   - Eliminar cualquier "22/25" o "18/25" auto-asignado sin justificación
```

**Archivos a modificar**: architecture-reference.md, plan.md, work.md, review.md, solid-analyzer skill

---

### Fase 6: Implementar flujo de specs (feature → baseline)

**Objetivo**: Que las specs de features completadas enriquezcan el baseline para features futuras.

**Prompt para ejecutar esta fase**:

```
Las specs generadas por cada feature se quedan aisladas en openspec/changes/{slug}/specs.md y nunca migran al baseline. Esto significa que cada feature nueva re-descubre las mismas entidades.

SOLUCIÓN:

1. En compound.md, añadir un Step 3d "Merge Feature Specs to Baseline":
   - Leer openspec/changes/{slug}/specs.md
   - Para cada entity_spec nueva → añadir a openspec/specs/entities/
   - Para cada api_contract nueva → añadir a openspec/specs/api/
   - Para cada business_rule nueva → añadir a openspec/specs/rules/
   - Usar spec-merger skill para detección de conflictos
   - Si hay conflicto con spec existente → documentar y no sobrescribir, marcar para revisión humana

2. En plan.md Phase 2 "Integration Analysis":
   - Antes de generar specs de feature, LEER openspec/specs/ baseline
   - Si una entidad ya existe en baseline → no re-especificar, solo referenciar
   - Si un endpoint ya existe → verificar compatibilidad, no duplicar

3. En discover.md --setup:
   - Generar baseline inicial: extraer entities, endpoints, rules del codebase existente
   - Escribirlos en openspec/specs/ con formato estándar

4. Crear directorio estándar:
   openspec/specs/
   ├── entities/          ← entity specs del baseline
   ├── api/               ← api contracts del baseline
   ├── rules/             ← business rules del baseline
   └── architecture-profile.yaml  ← (ya existe)
```

**Archivos a modificar**: compound.md, plan.md, discover.md

---

### Fase 7: Estandarizar terminología

**Objetivo**: Misma palabra = mismo concepto en todo el plugin.

**Prompt para ejecutar esta fase**:

```
El plugin usa términos diferentes para los mismos conceptos. Necesito estandarizar.

GLOSARIO CANÓNICO (definir en framework_rules.md, sección nueva "Terminology"):

| Término canónico | NO usar | Definición |
|-----------------|---------|-----------|
| Workflow State | "Feature state", "Phase state" | Sección en tasks.md que rastrea el progreso de cada fase |
| Checkpoint | "Snapshot" (cuando es git commit) | Git commit atómico al completar un task, con resumen en tasks.md |
| Session Snapshot | "Checkpoint" (cuando es estado completo) | Exportación completa del estado para reanudar en otra sesión |
| Role | (no confundir con Agent) | Planner, Implementer, Reviewer — definen permisos y contexto |
| Agent | (no confundir con Role) | Instancia fork con contexto aislado (code-reviewer, codebase-analyzer, etc.) |
| Skill | (no confundir con Command) | Operación invocable por el usuario (/multi-agent-workflow:X) |
| Command | (no confundir con Skill) | Workflow step invocable (/workflows:X) |
| Pattern (architecture) | | Design pattern (Strategy, Repository, etc.) |
| Pattern (learned) | | Patrón exitoso descubierto por compound |
| BCP | "Correction loop", "fix loop" | Bounded Correction Protocol — 3 tipos de desviación, límites adaptativos |

ACCIONES:
1. Añadir glosario a framework_rules.md
2. Buscar y reemplazar en TODOS los archivos del plugin:
   - "Feature state" → "Workflow State"
   - "Snapshot" (cuando es git commit) → "Checkpoint"
   - Verificar que "Agent" nunca se usa para Roles y viceversa
```

**Archivos a modificar**: framework_rules.md + búsqueda global en todos los archivos

---

### PRIORIDAD 3: Mejoras Estructurales (Fases 8-10)

---

### Fase 8: Simplificar plan.md

**Objetivo**: Reducir de 1365 líneas a ~700 manteniendo toda la funcionalidad.

**Prompt para ejecutar esta fase**:

```
plan.md es el archivo más grande (1365 líneas) y el más importante (80% del valor). Necesita ser más conciso sin perder funcionalidad.

PRINCIPIO: Instruir al agente QUÉ hacer, no CÓMO se ve el output. El agente sabe generar markdown.

ACCIONES ESPECÍFICAS:

1. ELIMINAR bloques de "ejemplo de output" que ocupan >20 líneas cada uno:
   - El ejemplo de proposal.md (~30 líneas) → reducir a 5 líneas mostrando las secciones esperadas
   - El ejemplo de specs.md (~40 líneas) → reducir a lista de secciones requeridas
   - El ejemplo de design.md (~50 líneas) → reducir a estructura esperada (headers)
   - Los ejemplos de tasks en Phase 4 (~30 líneas) → reducir a formato de tabla

2. ELIMINAR repeticiones del "Write-Then-Advance" rule (mencionado 5+ veces):
   - Definir UNA VEZ al inicio: "Rule: Each phase writes its output file before advancing. Never advance without writing."
   - Eliminar las otras 4 menciones

3. CONSOLIDAR Quality Gates:
   - Hay 4 Quality Gate blocks (Phases 1-4) con formato similar
   - Mantenerlos pero reducir a tabla:
   ```
   | Check | Pass Criteria |
   |-------|--------------|
   | Output exists | {file} written and non-empty |
   | Content complete | All required sections present |
   | No contradictions | No conflicts with existing specs |
   ```

4. MANTENER intactos:
   - Phase transitions y sus prerequisitos
   - SOLID integration points (Steps 3.1, 3.2, 3.4)
   - Integration Analysis (pre/post hooks en Phase 2)
   - La lógica de scale-adaptive planning depth

5. RESULTADO ESPERADO: ~700 líneas, mismo comportamiento, menos context consumption.
```

**Archivos a modificar**: plan.md

---

### Fase 9: Documentar rutas de error y recovery

**Objetivo**: Cada fase del workflow tiene una ruta de recuperación documentada.

**Prompt para ejecutar esta fase**:

```
El plugin solo documenta el happy path. Cuando algo falla, el agente no sabe qué hacer.

Añadir una sección "Error Recovery" al final de cada workflow command. Formato:

## Error Recovery

| Situación | Acción |
|----------|--------|
| tasks.md no existe | Crear desde template (core/templates/tasks-template.md) con Phase Status = todo PENDING |
| Fase anterior no COMPLETED | STOP. Informar: "Phase X must complete first. Run /workflows:X" |
| Specs conflictan con baseline | Documentar conflicto en proposal.md. Pedir decisión humana |
| SOLID NON_COMPLIANT en diseño | Volver a Phase 3.2. Max 3 iteraciones, luego pedir decisión humana |
| Test falla en BCP y agota iteraciones | Invocar diagnostic-agent. Si no resuelve, BLOCK task en tasks.md |
| Review REJECTED | Registrar feedback en tasks.md. Volver a /workflows:work con feedback como input |
| Archivo de output corrupto/vacío | Re-ejecutar la phase. No avanzar sin output válido |

ARCHIVOS A ACTUALIZAR:
1. route.md: Error si no hay proyecto configurado → sugerir /workflows:discover --setup
2. plan.md: Errores en cada Phase + error si spec-validator falla
3. work.md: Errores en TDD, BCP, checkpoint + error si design.md no existe
4. review.md: Errores si work no COMPLETED + qué pasa después de REJECTED
5. compound.md: Errores si review no APPROVED + qué pasa si architecture-profile.yaml no existe
6. quick.md: Error si scope crece más de lo esperado → redirección a plan
```

**Archivos a modificar**: route.md, plan.md, work.md, review.md, compound.md, quick.md

---

### Fase 10: Reescribir SESSION_CONTINUITY.md

**Objetivo**: Documentar solo lo que funciona, eliminar lo aspiracional.

**Prompt para ejecutar esta fase**:

```
SESSION_CONTINUITY.md tiene 570 líneas, de las cuales ~60% referencia comandos que no existen (/workflows:snapshot, /workflows:restore, etc.).

REESCRITURA COMPLETA. Objetivo: ~200 líneas.

ESTRUCTURA:

## Session Continuity

### How State Persists
1. **tasks.md** — Workflow state (fase actual, estado de tasks, resume information)
2. **Git commits** — Code checkpoints via `checkpoint` skill
3. **openspec/ files** — Specs, design, proposal persisten en disco

### Resuming a Session
1. Read `openspec/changes/{slug}/tasks.md`
2. Identify current phase and last completed task
3. Read Resume Information section
4. Continue from next pending task

### Creating a Checkpoint (during work)
- Invoke `checkpoint` skill after completing a task
- Creates atomic git commit with conventional message
- Updates tasks.md with checkpoint hash

### Syncing State (multi-session)
- Invoke `git-sync` skill to push/pull state
- All state is in files (tasks.md, specs, design, code)
- No external state store needed

### What to Do When Resuming Fails
- If tasks.md is corrupted: recreate from git log + code state
- If openspec/ is missing: re-run /workflows:discover --setup
- If mid-task: check git diff for uncommitted work, decide to commit or discard

ELIMINAR: Todas las referencias a /workflows:snapshot, /workflows:restore, /workflows:reload, /workflows:parallel. Estas funcionalidades no existen como comandos separados.
```

**Archivos a modificar**: SESSION_CONTINUITY.md

---

### Fase 11: Reflection Pattern en Quality Gates

> [NEW — Gap A: Reflection Pattern]

**Objetivo**: Que los Quality Gates incluyan auto-crítica real, no solo checks de checklist.

**Prompt para ejecutar esta fase**:

```
Los Quality Gates actuales verifican condiciones estáticas (archivo existe, secciones presentes). Falta un paso de reflexión donde el agente revise críticamente su propio output.

PROBLEMA: El agente genera output → verifica checklist → avanza. Nunca se pregunta "¿esto realmente tiene sentido?" o "¿qué debilidades tiene mi output?"

SOLUCIÓN: Añadir un paso de "Self-Review" en cada Quality Gate de plan.md:

Antes de verificar la checklist formal, el agente debe:
1. Cambiar de rol mental: de "planificador" a "revisor crítico"
2. Responder estas preguntas sobre su propio output:
   - "¿Qué suposiciones estoy haciendo que no he validado?"
   - "¿Qué podría fallar con este diseño/spec/task?"
   - "¿Hay gaps lógicos entre lo que el usuario pidió y lo que estoy proponiendo?"
   - "¿Estoy sobre-diseñando o sub-diseñando?"
3. Si identifica debilidades → corregir ANTES de pasar la checklist formal
4. Documentar las debilidades encontradas y cómo se resolvieron en el Decision Log

Formato en plan.md para cada Quality Gate:
```
### Quality Gate: Phase N
#### Step 1: Self-Review (Reflection)
- Switch to critical reviewer role
- Identify weaknesses, unvalidated assumptions, logical gaps
- Fix issues found. Log in Decision Log.
#### Step 2: Formal Checklist
- [existing checks]
```

ARCHIVOS A ACTUALIZAR: plan.md (4 Quality Gates), work.md (checkpoint validation), review.md (QA checklist)
```

**Archivos a modificar**: plan.md, work.md, review.md

---

### Fase 12: Test Contract Sketch + Security Threat Analysis en diseño

> [NEW — Gaps D + G: Testing temprano + Seguridad]

**Objetivo**: Validar que specs sean testeables y seguras antes de llegar a implementación.

**Prompt para ejecutar esta fase**:

```
Dos gaps críticos en el diseño: no se valida testeabilidad temprana ni se analiza seguridad.

PROBLEMA 1 (Gap D): Los tests solo aparecen como "Tests to Write FIRST" dentro de cada task en Phase 4.
No hay validación de que las specs sean testeables a nivel de contrato antes de implementar.

PROBLEMA 2 (Gap G): El plan menciona SOLID exhaustivamente pero no tiene seguridad.
No hay threat modeling como parte del diseño.

SOLUCIÓN: Añadir 2 sub-pasos en plan.md Phase 2 y Phase 3:

EN PHASE 2 (después de generar specs):
Sub-paso 2.5: "Test Contract Sketch"
- Para cada spec principal, bosquejar 2-3 tests de aceptación en formato Given/When/Then
- No código — solo contratos: "Given {precondición}, When {acción}, Then {resultado esperado}"
- Si un spec no puede expresarse como test de aceptación → el spec es ambiguo → volver a refinar
- Escribir en specs.md sección "## Acceptance Test Contracts"

EN PHASE 3 (después de diseño técnico):
Sub-paso 3.5: "Security Threat Analysis"
- Identificar superficie de ataque de la nueva feature
- Verificar: validación de inputs en boundaries, autenticación/autorización requerida, datos sensibles involucrados
- Si el cambio expone nuevos endpoints/inputs → documentar mitigaciones en design.md sección "## Security Considerations"
- Si no hay superficie de ataque nueva → documentar "No new attack surface" y seguir

ARCHIVOS A ACTUALIZAR: plan.md (Phase 2 y Phase 3)
```

**Archivos a modificar**: plan.md

---

### Fase 13: Chunking, Feedback Loop y Rollback Protocol

> [NEW — Gaps B + C + J: Retroalimentación, chunking y recovery]

**Objetivo**: Prevenir outputs monolíticos, capturar aprendizaje, y permitir volver atrás.

**Prompt para ejecutar esta fase**:

```
Tres gaps relacionados con la robustez del proceso.

GAP C — CHUNKING:
Los LLMs fallan con outputs monolíticos. El plan pide generar archivos enteros de una vez.

SOLUCIÓN: Añadir directivas de "max chunk size" en plan.md:
- Phase 2: "Si hay más de 5 specs, generar en grupos de 3, verificar cada grupo, luego consolidar"
- Phase 3: "Si el diseño tiene más de 3 componentes, diseñar uno a uno, verificar coherencia, luego integrar"
- Phase 4: "Si hay más de 8 tasks, generar en bloques de 5, validar dependencias entre bloques"
- Regla general: "Ningún output individual debe exceder 200 líneas. Si lo hace, dividir en sub-outputs."

GAP B — FEEDBACK LOOP:
El proceso es lineal sin retroalimentación para futuras ejecuciones.

SOLUCIÓN: Añadir en compound.md un paso final "Retrospective":
- Después de que compound actualiza el profile, generar `openspec/changes/{slug}/99_retrospective.md`:
  - Decisiones que fueron revisadas o rechazadas por el usuario
  - Patterns que funcionaron bien
  - Gaps descubiertos tarde en el proceso
  - Tiempo estimado vs real por fase (si disponible)
- En plan.md Phase 1, añadir: "Si existe algún 99_retrospective.md previo, leerlo para evitar repetir errores"

GAP J — ROLLBACK:
No hay guía sobre qué hacer si una fase produce resultados incorrectos.

SOLUCIÓN: Añadir en framework_rules.md sección "Rollback Protocol":
- "Si el Quality Gate de Phase N falla después de 3 iteraciones:
  1. Registrar en Decision Log: qué falló y por qué
  2. Presentar al usuario: opciones son (a) volver a Phase N-1 y revisar supuestos, (b) continuar con excepción documentada, (c) cancelar feature
  3. Si el usuario elige (a): marcar Phase N como PENDING en tasks.md, Phase N-1 como IN_PROGRESS
  4. El output de Phase N-1 se preserva como borrador — no se elimina"

ARCHIVOS A ACTUALIZAR: plan.md, compound.md, framework_rules.md
```

**Archivos a modificar**: plan.md, compound.md, framework_rules.md

---

### Fase 14: Right-Sizing de modelo por fase

> [NEW — Gap E: Estrategia de modelo]

**Objetivo**: Recomendar qué tipo de modelo usar en cada fase para optimizar coste/calidad.

**Prompt para ejecutar esta fase**:

```
Las mejores prácticas recomiendan usar modelos grandes para razonamiento complejo
y modelos pequeños para verificación/templating.

SOLUCIÓN: Añadir en CAPABILITY_PROVIDERS.md una sección "Model Recommendations by Phase":

| Fase | Tipo de modelo recomendado | Razón |
|------|---------------------------|-------|
| Phase 1 (Understand) | Grande (reasoning) | Análisis profundo del codebase y requisitos |
| Phase 2 (Specs) | Grande (reasoning + creativity) | Generar specs requiere creatividad + estructura |
| Phase 3 (Design) | Grande (reasoning) | Decisiones arquitectónicas complejas |
| Quality Gates | Pequeño/rápido | Verificación contra checklist, no requiere creatividad |
| Phase 4 (Tasks) | Mediano | Templating estructurado, menos razonamiento |
| Work (implementación) | Grande | Generación de código requiere máxima calidad |
| Review | Mediano-Grande | Análisis crítico pero sobre artefactos ya existentes |
| Compound | Pequeño-Mediano | Actualización de profiles, merge de specs |

NOTA: Esto es una RECOMENDACIÓN, no un enforcement. El plugin es model-agnostic.
Los capability providers ya abstraen el modelo. Esta tabla guía al usuario sobre
qué modelo configurar en cada provider.

ARCHIVOS A ACTUALIZAR: CAPABILITY_PROVIDERS.md (sección nueva)
```

**Archivos a modificar**: CAPABILITY_PROVIDERS.md

---

## Orden de Ejecución Recomendado

```
PRIORIDAD 1 — Corrección (sin esto el plugin es unreliable):
  Fase 1:  Referencias rotas              ← Confiabilidad básica
  Fase 2:  Estado de workflow + Decision Log  ← Reanudación funcional + trazabilidad [Gap I]
  Fase 3:  Reducir contexto               ← Rendimiento del agente
  Fase 4:  Clarificar routing + HITL       ← UX del usuario + checkpoints humanos [Gap F]

PRIORIDAD 2 — Coherencia (calidad del output):
  Fase 5:  SOLID enforcement + Justification  ← Calidad de diseño sin gaming [Gap H]
  Fase 6:  Flujo de specs                 ← Compound engineering real
  Fase 7:  Terminología                   ← Comunicación entre agentes
  Fase 11: Reflection Pattern             ← Quality Gates con auto-crítica real [Gap A]

PRIORIDAD 3 — Estructura (robustez y optimización):
  Fase 8:  Simplificar plan.md            ← Context budget
  Fase 9:  Rutas de error                 ← Robustez
  Fase 10: SESSION_CONTINUITY             ← Documentación limpia
  Fase 12: Test Contracts + Security      ← Validación temprana [Gaps D + G]
  Fase 13: Chunking + Feedback + Rollback ← Robustez del proceso [Gaps B + C + J]
  Fase 14: Right-sizing de modelo         ← Optimización de coste [Gap E]
```

Dependencias:
- Fase 2 antes de Fase 4 (routing necesita saber el formato de tasks.md)
- Fase 5 antes de Fase 6 (las specs necesitan saber qué valida SOLID)
- Fase 7 se puede hacer en paralelo con cualquier otra
- Fase 11 después de Fase 8 (simplificar plan.md antes de añadir Reflection Pattern)
- Fase 12 después de Fase 8 (añadir sub-pasos a un plan.md ya simplificado)
- Fase 13 después de Fases 9 y 11 (rollback complementa error recovery y reflection)
- Fase 14 es independiente, puede hacerse en cualquier momento
- Fases 8-10 son independientes entre sí

---

## Prompt Maestro para Ejecutar Todo

Si deseas ejecutar todas las fases en una sola sesión larga, usa este prompt:

```
Estoy mejorando el plugin multi-agent-workflow v3.1.0. El plan completo está en plans/plugin-improvement-plan.md.

Ejecuta las fases en este orden: 1, 2, 3, 4, 5, 7, 6, 11, 8, 12, 9, 10, 13, 14.

Para cada fase:
1. Lee el prompt específico en el plan
2. Lee los archivos afectados
3. Haz los cambios descritos
4. Verifica que no introduces nuevas referencias rotas
5. Haz commit con mensaje: "refactor(plugin): phase N - {descripción corta}"

REGLAS:
- No crear archivos nuevos excepto donde el plan lo indica explícitamente
- No añadir features — solo consolidar, corregir y simplificar
- Mantener el estilo existente de cada archivo
- Si un cambio requiere decisión ambigua, preguntarme antes de actuar
- Verificar al final: grep -r "22/25\|18/25\|quickstart\|agent-memory\|ddd_rules\|spec-validator\|opsx:archive" debe dar 0 resultados
```

---

## Verificación Final

Después de implementar todas las fases, ejecutar:

```bash
# 0 resultados esperados (referencias rotas eliminadas):
grep -r "agent-memory" plugins/multi-agent-workflow/
grep -r "ddd_rules" plugins/multi-agent-workflow/
grep -r "spec-validator" plugins/multi-agent-workflow/
grep -r "opsx:archive" plugins/multi-agent-workflow/
grep -r "/workflows:quickstart" plugins/multi-agent-workflow/
grep -r "/workflows:snapshot" plugins/multi-agent-workflow/
grep -r "/workflows:restore" plugins/multi-agent-workflow/
grep -r "/workflows:reload" plugins/multi-agent-workflow/
grep -r "/workflows:parallel" plugins/multi-agent-workflow/
grep -r "22/25\|18/25" plugins/multi-agent-workflow/

# Debe existir:
ls plugins/multi-agent-workflow/core/templates/tasks-template.md

# Verificar reducción de tamaño:
wc -l plugins/multi-agent-workflow/commands/workflows/plan.md  # objetivo: ~700
wc -l plugins/multi-agent-workflow/core/docs/SESSION_CONTINUITY.md  # objetivo: ~200
```

---

## Métricas de Éxito

| Métrica | Antes | Después |
|---------|-------|---------|
| Referencias rotas | 10+ | 0 |
| Líneas en plan.md | 1365 | ~700 |
| Líneas en SESSION_CONTINUITY.md | 570 | ~200 |
| Formatos de tasks.md | 3-4 | 1 |
| Definición SOLID enforcement | Ambigua | Matriz única + justificación textual |
| Specs migran a baseline | No | Sí |
| Rutas de error documentadas | 0 | 6 workflows |
| Terminología consistente | No | Glosario + enforcement |
| Quality Gates con Reflection | No | Sí (4 gates + work + review) |
| HITL checkpoints entre fases | 2 (inicio/fin) | 4 (Phase 2→3, 3→4, + inicio/fin) |
| Decision Log / Trazabilidad | No | Sí (en tasks.md) |
| Test Contracts tempranos | No | Sí (Phase 2.5) |
| Security Analysis en diseño | No | Sí (Phase 3.5) |
| Chunking de outputs | No | Sí (directivas por fase) |
| Feedback Loop / Retrospectiva | No | Sí (99_retrospective.md) |
| Rollback Protocol | No | Sí (en framework_rules.md) |
| Model recommendations por fase | No | Sí (en CAPABILITY_PROVIDERS.md) |

---

## Lo Que NO Cambia

- El flujo general: Route → Shape → Plan → Work → Review → Compound
- Los 4 archivos OpenSpec (proposal, specs, design, tasks)
- Los 8 agentes y 14 skills (excepto correcciones de referencias)
- Los 3 roles (planner, implementer, reviewer)
- TDD como metodología
- BCP como protocolo de corrección
- Compound Engineering como filosofía
- SOLID contextual — se mantiene pero ahora con justificación textual obligatoria (no solo scores)
- Capability Providers y model-agnostic design
- Context Activation Model (Fowler taxonomy)

---

## Fuentes

### Análisis interno
- Revisión completa del codebase del plugin (22+ archivos, ~8500 líneas)
- Validación manual de todas las referencias internas

### Mejores prácticas 2025-2026
- [Addy Osmani — LLM coding workflow 2026](https://addyosmani.com/blog/llm-coding-workflow/)
- [ByteByteGo — Top AI Agentic Workflow Patterns](https://bytebytego.com/)
- [Google Cloud — Design patterns for agentic AI](https://cloud.google.com/architecture)
- [AWS — Agentic AI patterns](https://aws.amazon.com/architecture/)
- [Microsoft — AI Agent Orchestration Patterns](https://learn.microsoft.com/)
- [UiPath — 10 best practices for reliable AI agents](https://www.uipath.com/)
- [Microsoft Engineering — AI-Powered Code Reviews](https://devblogs.microsoft.com/)
- [LangChain — State of Agent Engineering](https://www.langchain.com/)
- [Permit.io — HITL for AI Agents Best Practices](https://www.permit.io/)
- [Qodo — AI Code Review Pattern Predictions 2026](https://www.qodo.ai/)
- [arXiv — Production-Grade Agentic AI Workflows](https://arxiv.org/)
- [Digital Applied — Practical Agentic Engineering 2025](https://digitalapplied.com/)
- [CodeSIM, RTADev — Early test validation research](https://arxiv.org/)
