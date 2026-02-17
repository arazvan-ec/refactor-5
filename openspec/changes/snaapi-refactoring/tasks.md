# Implementation Tasks

## Progress
| Task | Status | Verify | Completed At |
|------|--------|--------|--------------|
| TASK-01: Crear interfaces de repositorio | PENDING | PHPStan L9 | - |
| TASK-02: Crear AbstractChainHandler | PENDING | Unit tests | - |
| TASK-03: Reemplazar MultimediaTrait por MultimediaService | PENDING | Unit tests + PHPStan | - |
| TASK-04: Reemplazar UrlGeneratorTrait por UrlGeneratorService | PENDING | Unit tests + PHPStan | - |
| TASK-05: Crear EditorialGroupProcessor | PENDING | Unit tests | - |
| TASK-06: Crear MultimediaResolver | PENDING | Unit tests | - |
| TASK-07: Crear SignatureResolver | PENDING | Unit tests | - |
| TASK-08: Crear EditorialResponseAssembler | PENDING | Unit tests | - |
| TASK-09: Refactorizar EditorialOrchestrator | PENDING | Integration tests | - |
| TASK-10: Refactorizar Chain Handlers (Body, Media, Multimedia) | PENDING | Unit tests | - |
| TASK-11: Agregar validacion en Compiler Passes | PENDING | Container tests | - |
| TASK-12: Crear jerarquia de excepciones | PENDING | PHPStan | - |
| TASK-13: Refactorizar tests del orquestador | PENDING | make test_unit | - |
| TASK-14: Verificacion final (full test suite) | PENDING | make tests | - |

## Task Details

### TASK-01: Crear Interfaces de Repositorio

**Role**: Implementer
**Methodology**: TDD (Red-Green-Refactor)
**Spec**: SPEC-F05

**Functional Requirement**:
- Crear interfaces para cada query client en `src/Application/Repository/`

**SOLID Requirements**:
- **DIP**: Interfaces en Application layer, implementaciones en Infrastructure
- **ISP**: Cada interfaz solo expone metodos usados por la capa de aplicacion

**Tests to Write FIRST**:
- [ ] test_editorial_repository_interface_is_implemented_by_query_client
- [ ] test_section_repository_interface_is_implemented_by_query_client

**Files to Create**:
- `src/Application/Repository/EditorialRepositoryInterface.php`
- `src/Application/Repository/SectionRepositoryInterface.php`
- `src/Application/Repository/MultimediaRepositoryInterface.php`
- `src/Application/Repository/JournalistRepositoryInterface.php`
- `src/Application/Repository/TagRepositoryInterface.php`
- `src/Application/Repository/MembershipRepositoryInterface.php`

**Acceptance Criteria**:
- [ ] Cada interfaz define los metodos usados en EditorialOrchestrator
- [ ] PHPStan level 9 sin errores
- [ ] Bindings configurados en services.yaml

---

### TASK-02: Crear AbstractChainHandler

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F03

**Functional Requirement**:
- Clase base abstracta con logica de register/resolve

**SOLID Requirements**:
- **SRP**: Solo gestiona registry y lookup
- **OCP**: Extensible via herencia
- **LSP**: Subclases respetan contrato

**Tests to Write FIRST**:
- [ ] test_register_and_resolve_handler
- [ ] test_resolve_unknown_key_throws_exception

**Files to Create**:
- `src/Application/DataTransformer/AbstractChainHandler.php`
- `tests/Application/DataTransformer/AbstractChainHandlerTest.php`

**Acceptance Criteria**:
- [ ] Logica de register/resolve implementada una sola vez
- [ ] Metodo abstracto `createNotFoundException` para excepciones especificas

---

### TASK-03: Reemplazar MultimediaTrait por MultimediaService

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F04

**Functional Requirement**:
- Convertir trait en servicio inyectable
- Tamanios configurables via parametros Symfony

**SOLID Requirements**:
- **SRP**: Servicio solo maneja logica de multimedia
- **DIP**: Inyeccion de dependencias, no traits

**Tests to Write FIRST**:
- [ ] test_get_multimedia_sizes_returns_configured_sizes
- [ ] test_generate_multimedia_url

**Files to Create**:
- `src/Infrastructure/Service/MultimediaService.php`
- `tests/Infrastructure/Service/MultimediaServiceTest.php`

**Files to Modify**:
- Clases que usan MultimediaTrait -> inyectar MultimediaService
- `config/services.yaml` -> agregar parametros de sizes

**Files to Delete**:
- `src/Infrastructure/Trait/MultimediaTrait.php`

---

### TASK-04: Reemplazar UrlGeneratorTrait por UrlGeneratorService

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F04

**Tests to Write FIRST**:
- [ ] test_generate_url_with_extension
- [ ] test_generate_url_without_extension

**Files to Create**:
- `src/Infrastructure/Service/UrlGeneratorService.php`
- `tests/Infrastructure/Service/UrlGeneratorServiceTest.php`

**Files to Delete**:
- `src/Infrastructure/Trait/UrlGeneratorTrait.php`

---

### TASK-05: Crear EditorialGroupProcessor

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F02

**Functional Requirement**:
- Servicio que procesa grupos de editoriales (inserted news y recommended)
- Error handling parametrizable (catch vs propagate)

**SOLID Requirements**:
- **SRP**: Solo procesa grupos de editoriales
- **DIP**: Depende de interfaces de repositorio

**Tests to Write FIRST**:
- [ ] test_process_group_returns_editorial_data
- [ ] test_process_group_with_catch_errors_logs_and_continues
- [ ] test_process_group_without_catch_errors_propagates_exception
- [ ] test_process_group_skips_non_visible_editorials

**Files to Create**:
- `src/Application/Service/EditorialGroupProcessor.php`
- `tests/Application/Service/EditorialGroupProcessorTest.php`

---

### TASK-06: Crear MultimediaResolver

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F01

**Functional Requirement**:
- Servicio dedicado a resolver multimedia de editoriales
- Encapsula logica de multimedia opening y recursos

**Tests to Write FIRST**:
- [ ] test_resolve_multimedia_by_id
- [ ] test_resolve_multimedia_returns_empty_on_missing
- [ ] test_resolve_multimedia_opening

**Files to Create**:
- `src/Application/Service/MultimediaResolver.php`
- `tests/Application/Service/MultimediaResolverTest.php`

---

### TASK-07: Crear SignatureResolver

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F01

**Functional Requirement**:
- Servicio para resolver firmas/journalists
- Optimizado para batch processing (resuelve N+1)

**Tests to Write FIRST**:
- [ ] test_resolve_signatures_returns_journalist_data
- [ ] test_resolve_signatures_handles_missing_journalist
- [ ] test_resolve_signatures_with_twitter_types

**Files to Create**:
- `src/Application/Service/SignatureResolver.php`
- `tests/Application/Service/SignatureResolverTest.php`

---

### TASK-08: Crear EditorialResponseAssembler

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F01

**Functional Requirement**:
- Servicio que ensambla la respuesta final del editorial
- Coordina data transformers para producir output

**Tests to Write FIRST**:
- [ ] test_assemble_response_with_full_data
- [ ] test_assemble_response_with_partial_data
- [ ] test_assemble_response_includes_all_transformations

**Files to Create**:
- `src/Application/Service/EditorialResponseAssembler.php`
- `tests/Application/Service/EditorialResponseAssemblerTest.php`

---

### TASK-09: Refactorizar EditorialOrchestrator

**Role**: Implementer
**Methodology**: Refactor (Green tests first, then restructure)
**Spec**: SPEC-F01

**Functional Requirement**:
- Reducir orquestador a coordinador delgado (<100 LOC)
- Delegar a servicios creados en TASK-05 a TASK-08

**SOLID Requirements**:
- **SRP**: Solo coordina, no implementa logica
- **DIP**: Depende de interfaces de servicios

**Pre-condition**: TASK-01 a TASK-08 completadas

**Files to Modify**:
- `src/Orchestrator/Chain/EditorialOrchestrator.php`

**Acceptance Criteria**:
- [ ] Constructor con <=5 dependencias
- [ ] Metodo execute() con <=50 lineas
- [ ] Output JSON identico al original
- [ ] Tests existentes siguen pasando

---

### TASK-10: Refactorizar Chain Handlers

**Role**: Implementer
**Methodology**: Refactor
**Spec**: SPEC-F03

**Pre-condition**: TASK-02 completada

**Files to Modify**:
- `src/Application/DataTransformer/BodyElementDataTransformerHandler.php`
- `src/Application/DataTransformer/Apps/Media/MediaDataTransformerHandler.php`
- `src/Orchestrator/Chain/Multimedia/MultimediaOrchestratorHandler.php`

**Acceptance Criteria**:
- [ ] Los tres handlers extienden AbstractChainHandler
- [ ] Logica duplicada eliminada
- [ ] Tests existentes siguen pasando

---

### TASK-11: Agregar Validacion en Compiler Passes

**Role**: Implementer
**Methodology**: TDD
**Spec**: SPEC-F08

**Files to Modify**:
- `src/DependencyInjection/Compiler/BodyDataTransformerCompiler.php`
- `src/DependencyInjection/Compiler/EditorialOrchestratorCompiler.php`
- `src/DependencyInjection/Compiler/MultimediaOrchestratorCompiler.php`
- `src/DependencyInjection/Compiler/MediaDataTransformerCompiler.php`
- `src/DependencyInjection/Compiler/WidgetDataTransformerCompiler.php`

---

### TASK-12: Crear Jerarquia de Excepciones

**Role**: Implementer
**Spec**: SPEC-F07

**Files to Create**:
- `src/Exception/ExternalServiceException.php`
- `src/Exception/ServiceUnavailableException.php`
- `src/Exception/TransformationException.php`
- `src/Exception/OrchestratorException.php`

---

### TASK-13: Refactorizar Tests del Orquestador

**Role**: Implementer
**Spec**: SPEC-F06

**Pre-condition**: TASK-09 completada

**Files to Modify**:
- `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` (simplificar)

**Acceptance Criteria**:
- [ ] Ningun test method supera 50 lineas de setup
- [ ] Eliminados `unset()` manuales innecesarios
- [ ] Cobertura mantenida o mejorada

---

### TASK-14: Verificacion Final

**Role**: Reviewer
**Pre-condition**: TASK-01 a TASK-13 completadas

**Verification Commands**:
```bash
make test_unit        # All unit tests pass
make test_stan        # PHPStan Level 9 clean
make test_cs          # Code style compliant
make test_infection   # MSI >= 79%
make tests            # Full suite green
```

**Acceptance Criteria**:
- [ ] Zero test failures
- [ ] Zero PHPStan errors
- [ ] MSI >= 79%
- [ ] No regressions in API output

---

## Decision Log
| Decision | Alternatives Considered | Rationale | Phase |
|----------|------------------------|-----------|-------|
| Interfaces en Application (no Domain) | Domain layer interfaces | Los query clients son detalle de infraestructura, la Application layer es el consumer natural | Design |
| AbstractChainHandler con herencia | Composicion via trait, Interface-only | Herencia minimiza duplicacion de implementacion, las 3 clases comparten logica identica | Design |
| Traits -> Servicios (no herencia) | Mantener traits, Crear base class | Servicios son testables en aislamiento, inyectables, y siguen DIP | Design |
| Error strategy como parametro booleano | Strategy pattern completo | Simple y suficiente para 2 comportamientos; strategy seria over-engineering | Design |
| Orden de tareas: interfaces primero | Servicios primero, Todo en paralelo | Las interfaces son la fundacion; todo lo demas depende de ellas | Planning |

## Workflow State
**Planner**: COMPLETED | **Implementer**: PENDING | **Reviewer**: PENDING
**Feature**: snaapi-refactoring
**Started**: 2026-02-17T20:15:00Z
**Last Updated**: 2026-02-17T20:15:00Z
**Last Phase**: Phase 4 | **Resume Point**: Step 0

### Planning Progress
| Phase | Status | Output File | Written At |
|-------|--------|-------------|------------|
| Step 0 (Load Specs) | COMPLETED | (context only) | 2026-02-17T20:10:00Z |
| Phase 1 (Understand) | COMPLETED | proposal.md | 2026-02-17T20:11:00Z |
| Phase 2 (Specs) | COMPLETED | specs.md | 2026-02-17T20:12:00Z |
| Phase 3 (Design) | COMPLETED | design.md | 2026-02-17T20:13:00Z |
| Phase 4 (Tasks) | COMPLETED | tasks.md | 2026-02-17T20:15:00Z |
| Completeness Check | COMPLETED | (verified) | 2026-02-17T20:15:00Z |

### Implementer Section
**Status**: PENDING
**Last Updated**: -

### QA / Reviewer Section
**Status**: PENDING
**Review Date**: -
