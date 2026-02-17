# Functional Specs: snaapi-refactoring

## SPEC-F01: Descomponer EditorialOrchestrator (God Object)

**Description**: El orquestador editorial debe delegar responsabilidades en servicios especializados para cumplir SRP.
**Acceptance Criteria**:
- [ ] El metodo `execute()` del orquestador no supera 50 lineas
- [ ] El constructor no tiene mas de 5 dependencias directas
- [ ] Cada servicio extraido tiene una unica responsabilidad
- [ ] La respuesta API final es identica a la actual (no breaking changes)
**Verification**: Comparar output JSON de endpoint antes/despues del refactoring

---

## SPEC-F02: Eliminar Duplicacion en Procesamiento de Editoriales

**Description**: La logica duplicada entre procesamiento de `insertedNews` y `recommendedEditorials` debe unificarse en un unico servicio reutilizable.
**Acceptance Criteria**:
- [ ] Existe un unico metodo/servicio que procesa grupos de editoriales
- [ ] `insertedNews` y `recommendedEditorials` usan el mismo servicio
- [ ] La diferencia de manejo de errores (try-catch vs none) es configurable
- [ ] No existe codigo copy-paste entre ambos flujos
**Verification**: Busqueda de patrones duplicados con herramientas de analisis estatico

---

## SPEC-F03: Unificar Chain Handlers bajo Abstraccion Generica

**Description**: Los tres handlers duplicados (`BodyElementDataTransformerHandler`, `MediaDataTransformerHandler`, `MultimediaOrchestratorHandler`) deben compartir una abstraccion comun.
**Acceptance Criteria**:
- [ ] Existe una clase base o interfaz generica `ChainHandler<T>`
- [ ] Los tres handlers extienden/implementan la abstraccion comun
- [ ] El patron add/lookup/execute es implementado una sola vez
- [ ] Las excepciones especificas se mantienen por tipo de handler
**Verification**: Tests unitarios de cada handler, verificar que la logica base no esta duplicada

---

## SPEC-F04: Reemplazar Traits por Servicios Inyectables

**Description**: `MultimediaTrait` y `UrlGeneratorTrait` deben convertirse en servicios independientes inyectados via constructor.
**Acceptance Criteria**:
- [ ] `MultimediaTrait` se convierte en `MultimediaService` inyectable
- [ ] `UrlGeneratorTrait` se convierte en `UrlGeneratorService` inyectable
- [ ] `CacheControl` trait se convierte en servicio o se integra al subscriber
- [ ] Los tamanios hardcodeados (`202w`, `144w`, `128w`) son configurables via parametros
- [ ] Las clases que usaban traits ahora inyectan servicios
**Verification**: Tests unitarios del servicio en aislamiento, PHPStan level 9

---

## SPEC-F05: Introducir Interfaces de Repositorio para Query Clients

**Description**: Los query clients concretos deben abstraerse tras interfaces de repositorio en la capa de dominio/aplicacion.
**Acceptance Criteria**:
- [ ] Existen interfaces: `EditorialRepositoryInterface`, `SectionRepositoryInterface`, `MultimediaRepositoryInterface`, `JournalistRepositoryInterface`, `TagRepositoryInterface`, `MembershipRepositoryInterface`
- [ ] Los query clients implementan estas interfaces
- [ ] El orquestador y servicios dependen de interfaces, no clases concretas
- [ ] Las interfaces estan en la capa `Application/` (no Infrastructure)
**Verification**: PHPStan level 9 verifica tipado correcto, tests con mocks de interfaces

---

## SPEC-F06: Refactorizar Tests del Orquestador

**Description**: El test monolitico de `EditorialOrchestrator` debe dividirse en tests focalizados para cada servicio extraido.
**Acceptance Criteria**:
- [ ] Cada servicio extraido tiene su propio archivo de test
- [ ] Ningun archivo de test supera 300 lineas
- [ ] Cada test method no supera 50 lineas de setup
- [ ] Se mantiene la cobertura de tests existente (no reduccion de MSI)
- [ ] Se eliminan los `unset()` manuales innecesarios en `tearDown()`
**Verification**: `make test_unit`, `make test_infection` (MSI >= 79%)

---

## SPEC-F07: Mejorar Manejo de Errores y Resiliencia

**Description**: Establecer un patron consistente de manejo de errores en todos los servicios.
**Acceptance Criteria**:
- [ ] Cada servicio tiene un contrato claro de excepciones documentado
- [ ] Los servicios de fetch externo tienen retry configurable o fail-fast consistente
- [ ] No hay `catch (\Throwable)` sin limite de reintentos
- [ ] Los retornos de error son consistentes (no mezcla de empty array, exception, null)
**Verification**: Revision de codigo, tests con servicios en fallo simulado

---

## SPEC-F08: Validar Type Safety en Compiler Passes

**Description**: Los compiler passes deben validar que los servicios registrados implementan la interfaz requerida.
**Acceptance Criteria**:
- [ ] `BodyDataTransformerCompiler` valida interfaz `BodyDataTransformerInterface`
- [ ] `EditorialOrchestratorCompiler` valida interfaz `EditorialOrchestratorInterface`
- [ ] `MultimediaOrchestratorCompiler` valida interfaz `MultimediaOrchestratorInterface`
- [ ] Los compiler passes lanzan excepcion si servicio no implementa interfaz esperada
**Verification**: Tests de container compilation con servicios invalidos

---

## Integration Analysis

### Entities Impact

#### MODIFIED (existing with changed structure)
| Entity | Change | Impact |
|--------|--------|--------|
| EditorialOrchestrator | Descompuesto en multiples servicios | HIGH - clase central del sistema |
| BodyElementDataTransformerHandler | Extiende ChainHandler base | MEDIUM |
| MediaDataTransformerHandler | Extiende ChainHandler base | MEDIUM |
| MultimediaOrchestratorHandler | Extiende ChainHandler base | MEDIUM |

#### NEW (services created)
| Service | Purpose | Layer |
|---------|---------|-------|
| EditorialGroupProcessor | Procesar grupos de editoriales (inserted/recommended) | Application |
| MultimediaResolver | Resolver multimedia asincrono | Application |
| SignatureResolver | Resolver firmas con batch fetching | Application |
| EditorialResponseAssembler | Ensamblar respuesta final | Application |
| MultimediaService | Logica de multimedia (reemplazo de trait) | Infrastructure |
| UrlGeneratorService | Generacion de URLs (reemplazo de trait) | Infrastructure |
| AbstractChainHandler | Handler generico base | Application |
| EditorialRepositoryInterface | Abstraccion de query client | Application |
| SectionRepositoryInterface | Abstraccion de query client | Application |
| MultimediaRepositoryInterface | Abstraccion de query client | Application |
| JournalistRepositoryInterface | Abstraccion de query client | Application |
| TagRepositoryInterface | Abstraccion de query client | Application |
| MembershipRepositoryInterface | Abstraccion de query client | Application |

### API Contracts Impact

#### UNCHANGED
| Endpoint | Reason |
|----------|--------|
| All existing endpoints | Refactoring interno, sin cambios en API publica |

### Business Rules Impact

#### NO CONFLICTS
El refactoring es puramente interno. No modifica reglas de negocio ni contratos de API.
