# Plan: De SOLID Numérico a Inteligencia Arquitectónica Contextual

**Fecha**: 2026-02-13
**Estado**: PENDIENTE
**Estimación**: ~28 archivos a modificar, 2 a fusionar, 1 a crear

---

## Contexto

El plugin multi-agent-workflow tiene un sistema SOLID basado en scores numéricos estáticos (≥22/25 para aprobar, ≥18/25 mínimo). Este sistema tiene 3 problemas fundamentales:

1. **Los scores son ficticios**: Asignar "Strategy = 25/25" como propiedad del patrón ignora que el score depende de la implementación, no del patrón abstracto.
2. **La matrix es estática y OOP-centric**: Asume clases + interfaces explícitas. No aplica a Go (interfaces implícitas), Python (duck typing), Rust (traits), JS funcional.
3. **No detecta — prescribe**: En vez de seguir los patrones que el proyecto YA usa, impone patrones de una lookup table.

## Objetivos

1. **Fusionar** `solid-pattern-matrix.md` + `architecture-quality-criteria.md` → un solo documento de referencia (no de config)
2. **Crear** `architecture-profile.yaml` como knowledge detectado del proyecto en `openspec/specs/`
3. **Que `discover --setup` lo genere** analizando el codebase real
4. **Que `compound` lo enriquezca** con cada feature completada
5. **Que `plan.md` Phase 3 lo lea** para diseñar soluciones coherentes con el proyecto
6. **Eliminar el score numérico X/25** y reemplazarlo con verificaciones contextuales por principio
7. **Reescribir `solid-analyzer`** para que analice contra el perfil del proyecto, no contra una tabla estática

---

## El Nuevo Modelo

### Antes (estático, numérico)

```
solid-pattern-matrix.md (tabla: violación → patrón con score fijo)
     +
architecture-quality-criteria.md (métricas: ≤200 líneas, ≤7 métodos)
     +
solid-analyzer skill (detecta violaciones → sugiere patrón de la tabla)
     ↓
Score: 22/25 → APPROVE
Score: 17/25 → REJECT
```

### Después (contextual, por principio)

```
architecture-reference.md (documento fusionado: principios + patrones + anti-patrones como REFERENCIA)
     +
openspec/specs/architecture-profile.yaml (DETECTADO del proyecto real)
     +
solid-analyzer skill (analiza código vs profile del proyecto + principios SOLID)
     ↓
Verificación por principio:
  SRP: "¿Esta clase tiene una razón para cambiar?" → SÍ/NO + evidencia
  OCP: "¿Se puede extender sin modificar?" → SÍ/NO + evidencia
  LSP: "¿Los subtipos respetan el contrato?" → SÍ/NO + evidencia (o N/A si no hay herencia)
  ISP: "¿Hay métodos no usados en interfaces?" → SÍ/NO + evidencia (o N/A si el lenguaje no tiene interfaces)
  DIP: "¿El dominio depende de abstracciones?" → SÍ/NO + evidencia
     ↓
Decisión: COMPLIANT / NEEDS_WORK (con qué principio y por qué) / NON_COMPLIANT (con evidencia)
```

### `architecture-profile.yaml` (ejemplo)

```yaml
# Generado por /workflows:discover --setup
# Enriquecido por /workflows:compound después de cada feature
# Ubicación: openspec/specs/architecture-profile.yaml

stack:
  language: php                    # php | typescript | python | go | rust | java | mixed
  framework: symfony               # symfony | laravel | express | fastapi | gin | spring | next | none
  paradigm: oop                    # oop | functional | mixed
  type_system: strong-static       # strong-static | strong-dynamic | weak-dynamic

architecture:
  pattern: hexagonal               # hexagonal | mvc | layered | clean | microservices | serverless | monolith | none
  layers:                          # Solo si pattern implica capas
    - name: Domain
      path: src/Domain/
      depends_on: []               # No depende de nada (DIP enforced)
    - name: Application
      path: src/Application/
      depends_on: [Domain]
    - name: Infrastructure
      path: src/Infrastructure/
      depends_on: [Domain, Application]
  separation_enforced: true        # ¿El proyecto ya respeta separación de capas?

patterns_detected:
  data_access: repository          # repository | active-record | query-builder | data-mapper | raw-sql
  dependency_management: constructor-injection  # constructor-injection | container-autowire | service-locator | manual
  error_handling: exceptions       # exceptions | result-types | error-codes | mixed
  async_pattern: none              # promises | async-await | callbacks | coroutines | channels | none
  testing_approach: tdd            # tdd | test-after | minimal | none

solid_relevance:
  # Cada principio tiene un nivel de relevancia para ESTE proyecto.
  # Esto evita forzar ISP en un proyecto Go o LSP en código sin herencia.
  srp:
    relevance: high                # high | medium | low
    metric: "classes ≤200 LOC, ≤7 public methods"
    when_violated: "Extract Class, then compose"
    reference_good: src/Domain/Entity/Order.php  # Ejemplo de buena SRP en este proyecto
  ocp:
    relevance: high
    metric: "no switch/if-else chains by type"
    when_violated: "Strategy pattern (this project uses it in src/Domain/Service/Pricing/)"
    reference_good: src/Domain/Service/Pricing/PricingStrategy.php
  lsp:
    relevance: medium              # medium porque el proyecto tiene poca herencia
    metric: "overrides respect parent contract"
    when_violated: "Composition over Inheritance"
  isp:
    relevance: high                # high porque PHP tiene interfaces explícitas
    metric: "interfaces ≤5 methods"
    when_violated: "Role Interfaces"
    reference_good: src/Domain/Repository/ReadableRepository.php
  dip:
    relevance: critical            # critical porque es hexagonal
    metric: "Domain/ imports zero from Infrastructure/"
    when_violated: "Port interface in Domain, Adapter in Infrastructure"
    reference_good: src/Domain/Port/UserRepositoryInterface.php

conventions:
  naming:
    classes: PascalCase
    methods: camelCase
    files: match-class-name
  structure:
    entity_path: src/Domain/Entity/
    usecase_path: src/Application/UseCase/
    repository_interface_path: src/Domain/Port/
    repository_impl_path: src/Infrastructure/Persistence/
  reference_files:
    entity: src/Domain/Entity/Order.php
    value_object: src/Domain/ValueObject/Money.php
    usecase: src/Application/UseCase/CreateOrderUseCase.php
    repository_interface: src/Domain/Port/OrderRepositoryInterface.php
    repository_impl: src/Infrastructure/Persistence/DoctrineOrderRepository.php
    controller: src/Infrastructure/Http/Controller/OrderController.php

quality_thresholds:
  # Thresholds adaptativos, no fijos
  max_class_loc: 200               # Descubierto del promedio del proyecto + margen
  max_public_methods: 7
  max_constructor_deps: 7
  max_interface_methods: 5
  max_files_per_simple_change: 5   # De architecture-quality-criteria C-BASE-01

# Enriquecido por compound:
learned_patterns:
  - pattern: "Value Object for validated fields (Email, Money, etc.)"
    confidence: high               # high = usado 3+ veces exitosamente
    source_features: [user-auth, payments, order-management]
  - pattern: "UseCase orchestrates, never Entity"
    confidence: medium
    source_features: [user-auth]

learned_antipatterns:
  - antipattern: "Putting hashing logic in Entity violates SRP"
    frequency: 2
    prevention: "Hashing, encryption, and encoding belong in Application or Infrastructure layer"
```

---

## Fases de Implementación

### Fase 1: Crear `architecture-reference.md` (fusión)

**Qué**: Fusionar `solid-pattern-matrix.md` + `architecture-quality-criteria.md` en un solo documento `core/architecture-reference.md`.

**Cambios en el documento fusionado**:
- Eliminar TODOS los scores numéricos X/25 de los patrones (Strategy ya no es "25/25")
- Mantener los decision trees (son útiles como referencia)
- Mantener las tablas de síntomas/violaciones (son útiles para detección)
- Mantener los anti-patrones (son universales)
- Añadir sección: "Cómo adaptar estos principios por paradigma" (OOP, funcional, mixed)
- Cambiar el framing: de "tabla de lookup" a "documento de referencia que el agente consulta"
- Eliminar la tabla "SOLID Compliance Score per Pattern" (la del 25/25, 24/25, etc.)
- Reemplazar "Pattern Selection Algorithm" (6 pasos con scores) por uno basado en: (1) qué hace el proyecto ya, (2) qué principio está violado, (3) qué patrón es más simple

**Archivos**:
- CREAR: `core/architecture-reference.md` (fusión)
- ELIMINAR: `core/solid-pattern-matrix.md`
- ELIMINAR: `core/architecture-quality-criteria.md`

---

### Fase 2: Crear template de `architecture-profile.yaml`

**Qué**: Crear el template que `discover --setup` usará para generar el perfil de cada proyecto.

**Archivos**:
- CREAR: `core/templates/architecture-profile-template.yaml` (template con comentarios explicativos)
- El archivo real `openspec/specs/architecture-profile.yaml` se genera en runtime por `discover`

---

### Fase 3: Actualizar `discover.md` para generar el perfil

**Qué**: Añadir un paso nuevo a discover que genere `architecture-profile.yaml` a partir del análisis del codebase.

**Cambios**:
- Nuevo Step (después del Step 3 actual "Analyze project structure"): "Generate Architecture Profile"
  - Detectar stack (language, framework, paradigm) — ya lo hace en Step 2
  - Detectar architecture pattern (hexagonal, mvc, etc.) — ya lo hace en Step 3
  - Detectar patterns_in_use (repository, active-record, etc.) — nuevo
  - Detectar solid_relevance por principio — nuevo
  - Detectar conventions (naming, structure, reference_files) — parcialmente nuevo
  - Detectar quality_thresholds del código existente (promedio LOC, métodos, etc.) — nuevo
  - Escribir `openspec/specs/architecture-profile.yaml`
- Actualizar Step 13 (Generate Project Profile) para referenciar el architecture-profile
- Actualizar references a thresholds de SOLID (quitar 22/25, 18/25)

**Archivos**:
- MODIFICAR: `commands/workflows/discover.md`

---

### Fase 4: Reescribir `solid-analyzer` skill

**Qué**: Cambiar de "detectar violaciones → score numérico" a "analizar contra perfil del proyecto → verificaciones contextuales por principio".

**CRÍTICO**: El solid-analyzer se usa en 3 momentos distintos del workflow con propósitos distintos. La reescritura debe cubrir los 3 modos:

#### Los 3 Modos del solid-analyzer

```
MODE 1 — BASELINE (Plan Step 3.1)
  Invocación: /workflow-skill:solid-analyzer --mode=baseline --path=src/relevant-module
  Input: path al código EXISTENTE + architecture-profile.yaml
  Propósito: Entender el estado actual ANTES de diseñar
  Output:
    - Patrones que el proyecto ya usa (detectados del código + confirmados por profile)
    - Violaciones actuales en el área afectada
    - Principios más/menos relevantes para este módulo
    - Archivos de referencia que ejemplifican buenas prácticas
  Quién lo consume: El agente en Plan Step 3.2 para diseñar soluciones coherentes

MODE 2 — DESIGN_VALIDATE (Plan Step 3.4)
  Invocación: /workflow-skill:solid-analyzer --mode=design --design=design.md
  Input: design.md (el diseño propuesto, NO hay código aún) + architecture-profile.yaml
  Propósito: Validar que el diseño respeta SOLID ANTES de implementar
  Output:
    Por cada principio relevante (según profile):
      SRP: COMPLIANT — "Cada clase tiene una responsabilidad: User=identidad, Email=validación"
      OCP: COMPLIANT — "Strategy para tokens permite añadir tipos sin modificar"
      LSP: N/A — "No hay herencia en este diseño"
      ISP: COMPLIANT — "Interfaces ≤5 métodos: UserRepositoryInterface(3)"
      DIP: COMPLIANT — "Domain define interfaces, Infrastructure implementa"
    Veredicto: COMPLIANT / NEEDS_WORK / NON_COMPLIANT
    Si NEEDS_WORK: qué principio y qué cambiar en el diseño
  Gate: Si NON_COMPLIANT → el diseño no avanza a Phase 4 (tasks)

MODE 3 — CODE_VERIFY (Work Steps 5/7, Review Phase 4)
  Invocación: /workflow-skill:solid-analyzer --mode=verify --path=src/modified-path --design=design.md
  Input: código recién escrito + design.md + architecture-profile.yaml
  Propósito: Verificar que la IMPLEMENTACIÓN cumple tanto SOLID como lo diseñado
  Output:
    Por cada principio relevante:
      SRP: COMPLIANT — "UserService:45 LOC, 3 public methods, 1 responsabilidad"
      DIP: COMPLIANT — "Domain/ tiene zero imports de Infrastructure/"
    Match con design.md:
      "design.md decía Strategy para tokens → implementado como JwtTokenGenerator + interface ✓"
      "design.md decía Repository pattern → implementado como DoctrineUserRepository ✓"
    Veredicto: COMPLIANT / NEEDS_WORK / NON_COMPLIANT
    Si NEEDS_WORK: qué principio viola, evidencia (archivo:línea), sugerencia contextual
  Gate: Si NON_COMPLIANT → no pasa el checkpoint (Work) o se rechaza (Review)
```

#### Flujo completo en el workflow

```
discover --setup
  └─ GENERA architecture-profile.yaml (detecta stack, patrones, convenciones)

plan Phase 3:
  ├─ Step 3.1: solid-analyzer MODE=BASELINE
  │   LEE: architecture-profile.yaml + código existente
  │   OUTPUT: baseline del proyecto → informa Step 3.2
  │
  ├─ Step 3.2: El agente DISEÑA soluciones
  │   LEE: architecture-profile.yaml (patrones del proyecto)
  │   LEE: architecture-reference.md (referencia de principios)
  │   LEE: output de Step 3.1 (baseline)
  │   ESCRIBE: design.md con razonamiento por principio
  │
  └─ Step 3.4: solid-analyzer MODE=DESIGN_VALIDATE
      LEE: design.md + architecture-profile.yaml
      GATE: NON_COMPLIANT → volver a Step 3.2

work:
  ├─ Step 5 (después de TDD green+refactor):
  │   solid-analyzer MODE=CODE_VERIFY --path=src/modified --design=design.md
  │   GATE: NON_COMPLIANT → BCP correction loop
  │
  └─ Step 7 (checkpoint):
      solid-analyzer MODE=CODE_VERIFY --path=src/Domain --design=design.md
      solid-analyzer MODE=CODE_VERIFY --path=src/Application --design=design.md
      solid-analyzer MODE=CODE_VERIFY --path=src/Infrastructure --design=design.md
      GATE: NON_COMPLIANT en cualquier layer → no pasa checkpoint

review Phase 4:
  └─ solid-analyzer MODE=CODE_VERIFY --path=src --design=design.md --scope=full
      GATE: NON_COMPLIANT → REJECTED

compound Step 3c:
  └─ ENRIQUECE architecture-profile.yaml con learnings
```

#### Lógica común a los 3 modos

```
1. LEER openspec/specs/architecture-profile.yaml
   - Si no existe: usar defaults razonables + advertir al usuario
   - Si existe: cargar stack, paradigm, solid_relevance, conventions, reference_files

2. Para cada principio SOLID:
   a. Verificar relevance del principio (del profile)
      - Si relevance = low → "N/A for this project" (skip)
      - Si relevance = critical → cualquier violación es NON_COMPLIANT
      - Si relevance = high → violación es NEEDS_WORK
      - Si relevance = medium → violación es NEEDS_WORK (con menor urgencia)
   b. Aplicar reglas de detección ADAPTADAS al stack del profile:
      - PHP/Java (oop): buscar clases, interfaces, imports entre layers
      - Go (oop+interfaces implícitas): buscar structs, package boundaries
      - Python (dynamic): buscar clases, imports, duck typing
      - TypeScript (mixed): buscar types/interfaces, module imports
      - Functional: buscar composición, side effects, coupling entre módulos
   c. Comparar contra reference_files del profile
      "¿Este código nuevo sigue el mismo patrón que el reference?"
   d. Emitir veredicto: COMPLIANT / NEEDS_WORK / NON_COMPLIANT + evidencia

3. Veredicto global:
   - COMPLIANT: todos los principios relevantes son COMPLIANT o N/A
   - NEEDS_WORK: algún principio es NEEDS_WORK pero ninguno NON_COMPLIANT
   - NON_COMPLIANT: algún principio con relevance critical/high es NON_COMPLIANT
```

#### Fallback sin architecture-profile.yaml

Si el profile no existe (proyecto no ha ejecutado `discover --setup`):
```
- Detectar stack del proyecto por heurísticas (package.json, composer.json, go.mod, etc.)
- Asumir todos los principios como relevance=medium
- No usar reference_files (no hay)
- Usar thresholds default (200 LOC, 7 métodos, 5 interface methods)
- Advertir: "Run /workflows:discover --setup for project-specific SOLID analysis"
```

**Archivos**:
- REESCRIBIR: `skills/workflow-skill-solid-analyzer.md`

---

### Fase 5: Actualizar `plan.md` Phase 3

**Qué**: Cambiar de "score ≥22/25" a "verificación contextual por principio". Conectar los modos BASELINE y DESIGN_VALIDATE del solid-analyzer.

**Cambios específicos por Step**:

**Step 3.1 "Analyze Existing Code"** (actualmente invoca `solid-analyzer --path` y espera score X/25):
- Cambiar invocación a: `solid-analyzer --mode=baseline --path=src/relevant-module`
- Cambiar output esperado de "Current SOLID score: X/25" a: "Patrones detectados, violaciones actuales, principios relevantes, archivos de referencia"
- Este output se usa como INPUT para Step 3.2

**Step 3.2 "Design Solutions with SOLID"** (actualmente llena tablas S:5 O:5...):
- Añadir: "LEER openspec/specs/architecture-profile.yaml para seguir patrones del proyecto"
- Reemplazar la tabla `| Principle | How It's Addressed | Pattern Used |` con sección narrativa:
  ```
  ## SOLID Compliance
  - **SRP**: COMPLIANT — UserService tiene una responsabilidad (orchestrate creation)
  - **OCP**: COMPLIANT — Strategy para tokens (siguiendo patrón existente en PricingStrategy.php)
  - **LSP**: N/A — No hay herencia en este diseño
  - **ISP**: COMPLIANT — UserRepositoryInterface tiene 3 métodos
  - **DIP**: COMPLIANT — Domain define interfaces, Infrastructure implementa
  ```
- Eliminar "Expected SOLID Score: 24/25"

**Step 3.3 "Pattern Selection Guide"** (actualmente tabla con "SOLID Addressed"):
- Cambiar de tabla fija a: "Consultar architecture-profile.yaml → patterns_detected y solid_relevance.*.when_violated para seleccionar patrones coherentes con el proyecto"
- Mantener la tabla como fallback cuando no hay profile, pero sin scores
- Cambiar referencia de `core/solid-pattern-matrix.md` a `core/architecture-reference.md`

**Step 3.4 "Verify SOLID Score"** (actualmente invoca `solid-analyzer --validate --design=design.md`):
- Cambiar a: `solid-analyzer --mode=design --design=design.md`
- Cambiar gate de "≥18/25 to proceed, ≥22/25 to approve" a: "COMPLIANT to proceed, NEEDS_WORK requires revision, NON_COMPLIANT blocks"
- Eliminar sección "SOLID Score Thresholds" (la tabla A/B/C/F con scores)

**Quality Gate Phase 3 Check 3** (actualmente: "Is the SOLID analysis present and non-trivial?"):
- Cambiar a: "Does each relevant SOLID principle have a reasoned verdict (COMPLIANT/N_A with justification)?"
- FAIL si algún principio con relevance≥medium no tiene justificación

**Task template** (actualmente: "SOLID Requirements: Use Strategy pattern..."):
- Cambiar a: "Architecture: Follow project pattern for X (reference: src/Domain/Service/Pricing/PricingStrategy.php)"
- El reference file viene del profile, no de la matrix

**Checklist final**:
- Quitar "Expected SOLID score ≥22/25"
- Poner "SOLID compliance: all relevant principles verified in design.md"

**Archivos**:
- MODIFICAR: `commands/workflows/plan.md` (~20 secciones a actualizar)

---

### Fase 6: Actualizar `work.md`

**Qué**: Cambiar las verificaciones de score numérico por `solid-analyzer --mode=verify`. Conectar el MODE 3 (CODE_VERIFY) del solid-analyzer.

**Cambios específicos por Step**:

**Task Execution Loop** (línea ~88, el resumen del loop):
- Paso 8: cambiar "CHECK SOLID (solid-analyzer) — must meet task thresholds" → "CHECK SOLID (solid-analyzer --mode=verify) — must be COMPLIANT"

**Step 5 "TDD + SOLID"** (línea ~276):
- Cambiar invocación de `solid-analyzer --path=src/modified-path` a: `solid-analyzer --mode=verify --path=src/modified-path --design=design.md`
- Cambiar "Must match expected score from design.md" a: "Must be COMPLIANT. If NEEDS_WORK, refactor before proceeding. If NON_COMPLIANT, enter BCP correction loop."

**Step 7 "Checkpoint"** (línea ~371):
- Cambiar `solid-analyzer --path=src/modified-path` a: `solid-analyzer --mode=verify --path=src/modified-path --design=design.md`
- Reemplazar la tabla "Checkpoint SOLID Requirements" por:
  ```
  | Checkpoint Type | Verification |
  |-----------------|-------------|
  | Domain layer | solid-analyzer --mode=verify --path=src/Domain --design=design.md |
  | Application layer | solid-analyzer --mode=verify --path=src/Application --design=design.md |
  | Infrastructure | solid-analyzer --mode=verify --path=src/Infrastructure --design=design.md |
  | Full feature | solid-analyzer --mode=verify --path=src --design=design.md --scope=full |
  ```
- Quitar "SRP ≥4/5, DIP ≥4/5" etc. → la relevancia viene del profile
- Quitar "Total score must be ≥18/25" → el gate es: ningún principio NON_COMPLIANT

**Per-task isolation output** (línea ~150):
- Cambiar "SOLID score" → "SOLID: COMPLIANT/NEEDS_WORK (per-principle details)"

**Ejemplo de checkpoint output** (línea ~480):
- Cambiar "SOLID Score: 21/25 (SRP: 5, OCP: 4, LSP: 4, ISP: 4, DIP: 4)" → "SOLID: COMPLIANT (SRP: ✓, OCP: ✓, LSP: N/A, ISP: ✓, DIP: ✓)"

**Stack-Specific Workflows** (líneas ~413-443):
- Eliminar scores per-layer ("SRP ≥4/5, DIP ≥4/5" etc.)
- Reemplazar por: "solid-analyzer --mode=verify verifica los principios relevantes según architecture-profile.yaml"

**Archivos**:
- MODIFICAR: `commands/workflows/work.md`

---

### Fase 7: Actualizar `review.md`

**Qué**: Cambiar de "SOLID score matches expected" a `solid-analyzer --mode=verify --scope=full`. Conectar el MODE 3 (CODE_VERIFY) del solid-analyzer para validación final.

**Cambios**:
- Quitar "SOLID score verification -- checks >= 18/25 minimum" → "SOLID compliance verification — must be COMPLIANT per solid-analyzer"
- QA Phase 4 invocación: cambiar `solid-analyzer --path=src --validate` a: `solid-analyzer --mode=verify --path=src --design=design.md --scope=full`
- Verificación: en vez de comparar score numérico, el reviewer verifica:
  1. solid-analyzer MODE=CODE_VERIFY retorna COMPLIANT
  2. Cada principio marcado como relevante en design.md fue implementado correctamente
  3. Los patrones diseñados (Strategy, Repository, etc.) existen en el código
- Decisión de rechazo: "NON_COMPLIANT on any principle with relevance≥high" en vez de "score < 18/25"
- Decisión de aprobación con notas: "NEEDS_WORK on principles with relevance=medium" (no bloquea, pero se documenta)

**Archivos**:
- MODIFICAR: `commands/workflows/review.md`

---

### Fase 8: Actualizar `compound.md` para enriquecer el profile

**Qué**: Después de cada feature, actualizar `architecture-profile.yaml` con lo aprendido.

**Cambios**:
- Nuevo paso (después de Step 3b "Update Agent Compound Memory"):
  - "Step 3c: Enrich Architecture Profile"
  - Leer `openspec/specs/architecture-profile.yaml`
  - Si se descubrió un nuevo patrón exitoso → añadirlo a `learned_patterns`
  - Si se descubrió un anti-patrón → añadirlo a `learned_antipatterns`
  - Si un reference_file cambió → actualizar
  - Si los thresholds demuestran ser incorrectos → ajustar
  - Escribir el archivo actualizado

**Archivos**:
- MODIFICAR: `commands/workflows/compound.md`

---

### Fase 9: Actualizar los demás archivos con referencias

**Qué**: Actualizar todas las referencias a los archivos viejos y scores numéricos en ~19 archivos.

**Lista completa de cambios**:

| Archivo | Cambio |
|---------|--------|
| `CLAUDE.md` | `solid-pattern-matrix.md` → `architecture-reference.md`. Quitar "score >= 22/25". Añadir "See `openspec/specs/architecture-profile.yaml` for project-specific patterns" |
| `README.md` | Igual que CLAUDE.md |
| `core/roles/planner.md` | Permisos read: reemplazar 2 archivos por 1. Quitar "Reject options with SOLID score < 18/25" → "Reject options that are NON_COMPLIANT" |
| `core/roles/implementer.md` | Quitar "must meet task thresholds" → "must be COMPLIANT per solid-analyzer" |
| `core/templates/spec-template.md` | Quitar scores numéricos. Reescribir SOLID section con formato por-principio. Quitar "Threshold: >=18/25" |
| `core/docs/CONTEXT_ENGINEERING.md` | `solid-pattern-matrix.md` → `architecture-reference.md`. Quitar "SOLID >=18/25" → "SOLID COMPLIANT" |
| `core/docs/CAPABILITY_PROVIDERS.md` | Actualizar references a solid-analyzer |
| `core/providers.yaml` | Actualizar comentarios |
| `commands/workflows/route.md` | Quitar "SOLID score >=18/25" → "SOLID compliance verified" |
| `commands/workflows/discover.md` | Quitar "SOLID >=22/25" y "SOLID >=18/25". Añadir generación de architecture-profile |
| `commands/workflows/help.md` | Quitar "score >= 22/25" → "SOLID compliance per project profile" |
| `skills/workflow-skill-criteria-generator.md` | Quitar scores numéricos. Cambiar "SOLID-Rigorous (no option with <18/25)" → "SOLID-Rigorous (no option NON_COMPLIANT)". Actualizar references |
| `agents/review/architecture-reviewer.md` | Quitar "SOLID Score: ${X}/25". Reescribir checklist con formato por-principio |

---

### Fase 10: Limpieza y verificación

**Verificaciones**:
```bash
# 0 resultados esperados:
grep -r "22/25" plugins/multi-agent-workflow/
grep -r "18/25" plugins/multi-agent-workflow/
grep -r "solid-pattern-matrix" plugins/multi-agent-workflow/
grep -r "architecture-quality-criteria" plugins/multi-agent-workflow/
grep -r "/25.*SOLID\|SOLID.*/25" plugins/multi-agent-workflow/

# Debe existir:
cat plugins/multi-agent-workflow/core/architecture-reference.md
cat plugins/multi-agent-workflow/core/templates/architecture-profile-template.yaml

# No debe existir:
ls plugins/multi-agent-workflow/core/solid-pattern-matrix.md      # DELETED
ls plugins/multi-agent-workflow/core/architecture-quality-criteria.md  # DELETED
```

---

## Resumen de Impacto

| Categoría | Archivos |
|-----------|----------|
| CREAR (2) | `core/architecture-reference.md`, `core/templates/architecture-profile-template.yaml` |
| ELIMINAR (2) | `core/solid-pattern-matrix.md`, `core/architecture-quality-criteria.md` |
| REESCRIBIR (1) | `skills/workflow-skill-solid-analyzer.md` |
| MODIFICAR FUERTE (4) | `plan.md`, `work.md`, `review.md`, `compound.md` |
| MODIFICAR LEVE (5) | `discover.md`, `route.md`, `help.md`, `criteria-generator`, `architecture-reviewer.md` |
| ACTUALIZAR REFS (8) | `CLAUDE.md`, `README.md`, `planner.md`, `implementer.md`, `spec-template.md`, `CONTEXT_ENGINEERING.md`, `CAPABILITY_PROVIDERS.md`, `providers.yaml` |
| **TOTAL** | **22 archivos** (2 creados, 2 eliminados, 18 modificados) |

---

## Orden de Ejecución Recomendado

```
Fase 1: Crear architecture-reference.md (fusión)           ← Base del cambio
Fase 2: Crear template architecture-profile.yaml            ← Define el nuevo modelo
Fase 4: Reescribir solid-analyzer                           ← Core del nuevo comportamiento
Fase 3: Actualizar discover.md                              ← Genera el profile
Fase 5: Actualizar plan.md                                  ← Consume el profile
Fase 6: Actualizar work.md                                  ← Verifica con nuevo formato
Fase 7: Actualizar review.md                                ← Valida con nuevo formato
Fase 8: Actualizar compound.md                              ← Enriquece el profile
Fase 9: Actualizar referencias (~13 archivos)               ← Consistencia
Fase 10: Verificación                                       ← Grep final
```

Nota: Fases 1+2 son independientes y se pueden hacer en paralelo. Fases 5+6+7 son independientes entre sí pero dependen de Fase 4.

---

## Verificación de Flujo: Dónde se usa cada pieza

Esta tabla es la verificación de que el plan cubre todos los puntos de contacto:

| Fase del Workflow | Qué lee | Qué invoca | Qué escribe | Modo solid-analyzer |
|-------------------|---------|------------|-------------|---------------------|
| `discover --setup` | Codebase existente | codebase-analyzer | `architecture-profile.yaml` | Ninguno |
| `plan` Step 0 | `architecture-profile.yaml` + `openspec/specs/` | — | — | — |
| `plan` Step 3.1 | Código existente + profile | solid-analyzer | — | **BASELINE** |
| `plan` Step 3.2 | profile + architecture-reference.md + baseline output | — | `design.md` (con SOLID por principio) | — |
| `plan` Step 3.4 | `design.md` + profile | solid-analyzer | — | **DESIGN_VALIDATE** |
| `work` Step 5 | Código nuevo + `design.md` + profile | solid-analyzer | — | **CODE_VERIFY** |
| `work` Step 7 | Código per-layer + `design.md` + profile | solid-analyzer | tasks.md (checkpoint) | **CODE_VERIFY** |
| `review` Phase 4 | Todo el código + `design.md` + profile | solid-analyzer | tasks.md (QA status) | **CODE_VERIFY** (scope=full) |
| `compound` Step 3c | `architecture-profile.yaml` + learnings | — | `architecture-profile.yaml` (enriquecido) | Ninguno |

Si alguna celda de esta tabla no tiene su cambio correspondiente en las Fases 1-10, el plan tiene un hueco. Verificar contra esta tabla después de cada fase implementada.

---

## Lo Que NO Cambia

- El workflow general (Route → Plan → Work → Review → Compound)
- Los 4 archivos OpenSpec (proposal, specs, design, tasks)
- El Baseline Freeze rule
- El BCP (3 tipos de desviación)
- TDD como metodología
- Los agentes y su función
- Los 14 skills (excepto solid-analyzer que se reescribe)
- Los 3 roles
- El concepto de que Phase 3 diseña con SOLID — solo cambia CÓMO se verifica
