# Design: snaapi-refactoring

## SOLID Baseline Analysis

### Current State (Before Refactoring)

| Principle | Status | Main Violations |
|-----------|--------|----------------|
| **SRP** | NON_COMPLIANT | EditorialOrchestrator tiene 14+ responsabilidades |
| **OCP** | NEEDS_WORK | Handlers duplicados, no extensibles sin modificacion |
| **LSP** | NEEDS_WORK | MultimediaOrchestrators retornan estructuras diferentes |
| **ISP** | NEEDS_WORK | Config bind monolitico, traits con dependencias ocultas |
| **DIP** | NON_COMPLIANT | Dependencia directa a 7 query clients concretos |

---

## Solution for SPEC-F01: Descomponer EditorialOrchestrator

**Approach**: Extraer responsabilidades en servicios especializados coordinados por un orquestador delgado.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Cada servicio tiene una unica responsabilidad
- **OCP**: COMPLIANT -- Nuevos procesadores se pueden agregar sin modificar orquestador
- **LSP**: N/A -- No hay jerarquia de herencia
- **ISP**: COMPLIANT -- Interfaces pequenas y focalizadas por servicio
- **DIP**: COMPLIANT -- Orquestador depende de interfaces, no implementaciones

**Architecture**:
```
EditorialOrchestrator (thin, <100 LOC)
  |-- EditorialGroupProcessor (inserted news + recommended)
  |-- MultimediaResolver (async multimedia fetching)
  |-- SignatureResolver (batch journalist/signature lookup)
  |-- EditorialResponseAssembler (combine all data into response)
```

**Files to Create**:
- `src/Application/Service/EditorialGroupProcessor.php`
- `src/Application/Service/MultimediaResolver.php`
- `src/Application/Service/SignatureResolver.php`
- `src/Application/Service/EditorialResponseAssembler.php`

**Files to Modify**:
- `src/Orchestrator/Chain/EditorialOrchestrator.php` (reduce to thin coordinator)

---

## Solution for SPEC-F02: Eliminar Duplicacion en Procesamiento de Editoriales

**Approach**: `EditorialGroupProcessor` recibe una coleccion de IDs editoriales y los procesa uniformemente. La diferencia de error handling se parametriza.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Un servicio, una responsabilidad: procesar grupo de editoriales
- **OCP**: COMPLIANT -- Error strategy inyectable sin modificar procesador
- **LSP**: N/A
- **ISP**: COMPLIANT -- Interface minima
- **DIP**: COMPLIANT -- Depende de EditorialRepositoryInterface

**Design**:
```php
class EditorialGroupProcessor
{
    public function processGroup(
        array $editorialIds,
        bool $catchErrors = false
    ): array {
        // Unified logic for both insertedNews and recommendedEditorials
        // $catchErrors = true for recommended (current behavior: catch + log + continue)
        // $catchErrors = false for inserted (current behavior: propagate exceptions)
    }
}
```

**Files to Create**:
- `src/Application/Service/EditorialGroupProcessor.php`

---

## Solution for SPEC-F03: Unificar Chain Handlers

**Approach**: Crear `AbstractChainHandler` con logica generica de registro y lookup.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Base handler solo gestiona registry/lookup
- **OCP**: COMPLIANT -- Extensible via herencia sin modificar base
- **LSP**: COMPLIANT -- Subclases respetan contrato de la clase base
- **ISP**: COMPLIANT -- Interface minima: register + handle
- **DIP**: COMPLIANT -- Depende de interface generica

**Design**:
```php
abstract class AbstractChainHandler
{
    /** @var array<string, object> */
    private array $handlers = [];

    public function register(string $key, object $handler): void
    {
        $this->handlers[$key] = $handler;
    }

    protected function resolve(string $key): object
    {
        if (!isset($this->handlers[$key])) {
            throw $this->createNotFoundException($key);
        }
        return $this->handlers[$key];
    }

    abstract protected function createNotFoundException(string $key): \Throwable;
}
```

**Files to Create**:
- `src/Application/DataTransformer/AbstractChainHandler.php`

**Files to Modify**:
- `src/Application/DataTransformer/BodyElementDataTransformerHandler.php`
- `src/Application/DataTransformer/Apps/Media/MediaDataTransformerHandler.php`
- `src/Orchestrator/Chain/Multimedia/MultimediaOrchestratorHandler.php`

---

## Solution for SPEC-F04: Reemplazar Traits por Servicios

**Approach**: Convertir cada trait en un servicio Symfony inyectable con contrato de interfaz.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Cada servicio tiene responsabilidad unica
- **OCP**: COMPLIANT -- Servicios extensibles via decorators
- **LSP**: N/A
- **ISP**: COMPLIANT -- Interfaces pequenas
- **DIP**: COMPLIANT -- Dependencias inyectadas, no acopladas

**Files to Create**:
- `src/Infrastructure/Service/MultimediaService.php` (reemplaza MultimediaTrait)
- `src/Infrastructure/Service/UrlGeneratorService.php` (reemplaza UrlGeneratorTrait)

**Files to Delete**:
- `src/Infrastructure/Trait/MultimediaTrait.php`
- `src/Infrastructure/Trait/UrlGeneratorTrait.php`

**Files to Modify**:
- Todas las clases que usan `MultimediaTrait` -> inyectar `MultimediaService`
- Todas las clases que usan `UrlGeneratorTrait` -> inyectar `UrlGeneratorService`
- `config/services.yaml` -> agregar parametros configurables para sizes

---

## Solution for SPEC-F05: Interfaces de Repositorio

**Approach**: Definir interfaces en Application layer, implementar en adapters wrapping los query clients existentes.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Interfaces definen contrato, adapters implementan
- **OCP**: COMPLIANT -- Nuevas implementaciones sin tocar consumidores
- **LSP**: COMPLIANT -- Adapters cumplen contrato de interfaz
- **ISP**: COMPLIANT -- Una interfaz por bounded context
- **DIP**: COMPLIANT -- Capa de aplicacion define contratos, infra implementa

**Files to Create**:
- `src/Application/Repository/EditorialRepositoryInterface.php`
- `src/Application/Repository/SectionRepositoryInterface.php`
- `src/Application/Repository/MultimediaRepositoryInterface.php`
- `src/Application/Repository/JournalistRepositoryInterface.php`
- `src/Application/Repository/TagRepositoryInterface.php`
- `src/Application/Repository/MembershipRepositoryInterface.php`

**Files to Modify**:
- `config/services.yaml` -> bind interfaces a query clients

---

## Solution for SPEC-F06: Refactorizar Tests

**Approach**: Dividir test monolitico en tests especificos por servicio. Cada nuevo servicio tiene su propio test file.

**SOLID Compliance**:
- **SRP**: COMPLIANT -- Cada test file testa un servicio
- **OCP**: N/A
- **LSP**: N/A
- **ISP**: N/A
- **DIP**: N/A

**Files to Create**:
- `tests/Application/Service/EditorialGroupProcessorTest.php`
- `tests/Application/Service/MultimediaResolverTest.php`
- `tests/Application/Service/SignatureResolverTest.php`
- `tests/Application/Service/EditorialResponseAssemblerTest.php`
- `tests/Application/DataTransformer/AbstractChainHandlerTest.php`
- `tests/Infrastructure/Service/MultimediaServiceTest.php`
- `tests/Infrastructure/Service/UrlGeneratorServiceTest.php`

**Files to Modify**:
- `tests/Orchestrator/Chain/EditorialOrchestratorTest.php` (simplificar dramaticamente)

---

## Solution for SPEC-F07: Manejo de Errores Consistente

**Approach**: Definir jerarquia de excepciones y contrato de error handling por capa.

**Design**:
```
Exception Hierarchy:
  SnapiException (base)
    ├── ExternalServiceException (para fallos de query clients)
    │   ├── EditorialNotFoundException
    │   ├── MultimediaNotFoundException
    │   └── ServiceUnavailableException
    ├── TransformationException (para fallos de transformacion)
    └── OrchestratorException (para fallos de orquestacion)
```

**Files to Create**:
- `src/Exception/ExternalServiceException.php`
- `src/Exception/ServiceUnavailableException.php`
- `src/Exception/TransformationException.php`
- `src/Exception/OrchestratorException.php`

---

## Solution for SPEC-F08: Type Safety en Compiler Passes

**Approach**: Agregar validacion de interfaz en cada compiler pass.

**Design**:
```php
// En cada compiler pass:
foreach ($taggedServices as $id => $tags) {
    $definition = $container->getDefinition($id);
    $class = $definition->getClass();
    if (!is_subclass_of($class, RequiredInterface::class)) {
        throw new \InvalidArgumentException(
            sprintf('Service "%s" must implement "%s"', $id, RequiredInterface::class)
        );
    }
}
```

**Files to Modify**:
- `src/DependencyInjection/Compiler/BodyDataTransformerCompiler.php`
- `src/DependencyInjection/Compiler/EditorialOrchestratorCompiler.php`
- `src/DependencyInjection/Compiler/MultimediaOrchestratorCompiler.php`
- `src/DependencyInjection/Compiler/MediaDataTransformerCompiler.php`
- `src/DependencyInjection/Compiler/WidgetDataTransformerCompiler.php`

---

## Architectural Impact

### Layer Analysis

| Layer | Impact Level | Changes Required |
|-------|--------------|------------------|
| **Application** | HIGH | 4 nuevos servicios, 6 interfaces, 1 clase abstracta |
| **Infrastructure** | MEDIUM | 2 nuevos servicios (reemplazo traits), config sizes |
| **Orchestrator** | HIGH | Refactor completo del God Object |
| **DependencyInjection** | MEDIUM | Validacion en compiler passes |
| **Tests** | HIGH | 7+ nuevos archivos de test, simplificacion de existentes |
| **Controller** | NONE | Sin cambios (API publica intacta) |

### Change Scope Summary

```
Files to CREATE:     ~20
Files to MODIFY:     ~15
Files to DELETE:      2 (traits)
Total files affected: ~37

Estimated LOC added:   ~1500
Estimated LOC modified: ~800
Estimated LOC deleted:  ~600
```

### Risk Assessment

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Regression en respuesta API | MEDIUM | HIGH | Tests de integracion antes/despues |
| Rotura de dependency injection | LOW | HIGH | Tests de container compilation |
| Perdida de cobertura de tests | LOW | MEDIUM | Verificar MSI antes y despues |
| Incompatibilidad con query clients (paquetes externos) | LOW | MEDIUM | Adapters wrapping, no modificar clients |
