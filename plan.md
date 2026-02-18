# Plan: Refactoring Completo - Modulo Vertical + Transformers Stateless

## Resumen

Reescribir el sistema de DataTransformers (eliminar herencia, hacer stateless) y reorganizar todo el flujo editorial en un modulo vertical (`src/Editorial/`) con 3 fases explicitas: **Resolve** → **Aggregate** → **Assemble**.

---

## Estructura de directorios objetivo

```
src/
├── Controller/V1/
│   └── EditorialController.php                         # MODIFY (inyecta nuevo handler)
│
├── Editorial/                                           # NEW MODULE
│   ├── GetEditorialHandler.php                         # Thin orchestrator: fetch → resolve → assemble
│   ├── EditorialAggregate.php                          # Immutable DTO (contrato entre Resolve y Assemble)
│   ├── BodyTransformContext.php                        # Typed context for body transformers (GAP 2)
│   ├── EditorialGroup.php                              # DTO para inserted/recommended editorials
│   │
│   ├── Resolver/                                        # FASE 1+2: Data collection
│   │   ├── EditorialResolverInterface.php
│   │   ├── ResolverPipeline.php                        # Orquesta todos los resolvers
│   │   ├── SectionResolver.php
│   │   ├── TagsResolver.php
│   │   ├── SignaturesResolver.php
│   │   ├── MultimediaResolver.php
│   │   ├── OpeningResolver.php
│   │   ├── BodyPhotosResolver.php
│   │   ├── InsertedNewsResolver.php
│   │   ├── RecommendedResolver.php
│   │   ├── MembershipResolver.php
│   │   └── CommentsResolver.php
│   │
│   └── Assembler/                                       # FASE 3: Response formatting
│       ├── ResponseAssemblerInterface.php
│       └── Apps/                                        # Formato: mobile apps
│           ├── AppsAssembler.php                       # Top-level: compone sub-assemblers
│           ├── EditorialMetadataAssembler.php          # id, url, titles, dates, flags...
│           ├── SectionAssembler.php                    # section + adsOptions/analyticsOptions
│           ├── TagsAssembler.php
│           ├── SignaturesAssembler.php                 # Journalist → signature array
│           ├── MultimediaAssembler.php                 # Opening + editorial multimedia
│           ├── StandfirstAssembler.php
│           ├── RecommendedAssembler.php                # Recommended editorials
│           │
│           ├── Body/                                    # Body element transformers (STATELESS)
│           │   ├── BodyElementTransformer.php          # Interface: transform(element, data): array
│           │   ├── BodyTransformerPipeline.php         # Registry + dispatcher (replaces 2 classes)
│           │   ├── ParagraphTransformer.php
│           │   ├── SubHeadTransformer.php
│           │   ├── BodyTagHtmlTransformer.php
│           │   ├── BodyTagSummaryTransformer.php
│           │   ├── BodyTagExplanatorySummaryTransformer.php
│           │   ├── BodyTagPictureTransformer.php
│           │   ├── BodyTagPictureMembershipTransformer.php
│           │   ├── BodyTagVideoTransformer.php
│           │   ├── BodyTagVideoYoutubeTransformer.php
│           │   ├── BodyTagMembershipCardTransformer.php
│           │   ├── BodyTagInsertedNewsTransformer.php
│           │   ├── LinkTransformer.php
│           │   ├── UnorderedListTransformer.php
│           │   └── NumberedListTransformer.php
│           │
│           └── Media/                                   # Media transformers (STATELESS)
│               ├── MediaTransformer.php                # Interface: transform(data, opening): array
│               ├── MediaTransformerPipeline.php        # Registry + dispatcher
│               ├── PhotoTransformer.php
│               ├── EmbedVideoTransformer.php
│               ├── WidgetTransformer.php
│               └── Widget/
│                   ├── WidgetTypeTransformer.php       # Interface
│                   ├── WidgetTransformerPipeline.php
│                   └── HtmlWidgetTransformer.php
│
├── Infrastructure/
│   ├── Service/
│   │   ├── PictureShots.php                            # KEEP
│   │   ├── Thumbor.php                                 # KEEP
│   │   ├── LinkExtractor.php                           # NEW (replaces LinksDataTransformer trait)
│   │   ├── UrlGenerator.php                            # NEW (replaces UrlGeneratorTrait)
│   │   └── MultimediaImageService.php                  # NEW (replaces MultimediaTrait)
│   ├── Enum/                                            # KEEP
│   └── Trait/                                           # DELETE in Phase 8
│
├── DependencyInjection/Compiler/
│   ├── BodyTransformerCompiler.php                     # NEW (tag: app.body_element_transformer)
│   ├── MediaTransformerCompiler.php                    # NEW (tag: app.media_transformer)
│   ├── WidgetTransformerCompiler.php                   # MODIFY (target new pipeline)
│   ├── EditorialOrchestratorCompiler.php               # KEEP
│   ├── MultimediaOrchestratorCompiler.php              # KEEP
│   ├── MultimediaFactoryCompiler.php                   # KEEP
│   └── WidgetLegacyCreatorHandlerCompiler.php          # KEEP
│
├── Orchestrator/                                        # KEEP structure
│   ├── OrchestratorChain.php                           # KEEP
│   ├── OrchestratorChainHandler.php                    # KEEP
│   ├── Chain/
│   │   ├── EditorialOrchestratorInterface.php          # KEEP
│   │   ├── EditorialOrchestrator.php                   # MODIFY: thin, delegates to GetEditorialHandler
│   │   └── Multimedia/                                  # KEEP (for opening resolution)
│   └── Exceptions/                                      # KEEP
│
└── Kernel.php                                           # MODIFY: register new compiler passes
```

---

## Archivos a ELIMINAR (Phase 8)

### DataTransformer hierarchy completa:
- `src/Application/DataTransformer/BodyElementDataTransformer.php` (interface)
- `src/Application/DataTransformer/BodyElementDataTransformerHandler.php`
- `src/Application/DataTransformer/BodyDataTransformer.php`
- `src/Application/DataTransformer/BodyDataTransformerInterface.php`
- `src/Application/DataTransformer/Apps/AppsDataTransformer.php` (interface)
- `src/Application/DataTransformer/Apps/DetailsAppsDataTransformer.php`
- `src/Application/DataTransformer/Apps/MultimediaDataTransformer.php` (interface)
- `src/Application/DataTransformer/Apps/DetailsMultimediaDataTransformer.php`
- `src/Application/DataTransformer/Apps/StandfirstDataTransformer.php`
- `src/Application/DataTransformer/Apps/RecommendedEditorialsDataTransformer.php`
- `src/Application/DataTransformer/Apps/JournalistsDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/ElementTypeDataTransformer.php` (abstract)
- `src/Application/DataTransformer/Apps/Body/ElementContentDataTransformer.php` (abstract)
- `src/Application/DataTransformer/Apps/Body/ElementContentWithLinksDataTransformer.php` (abstract)
- `src/Application/DataTransformer/Apps/Body/GenericListDataTransformer.php` (abstract)
- `src/Application/DataTransformer/Apps/Body/ParagraphDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/SubHeadDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagHtmlDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagSummaryDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagExplanatorySummaryDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagPictureDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagPictureMembershipDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagVideoDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagVideoYoutubeDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagMembershipCardDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/BodyTagInsertedNewsDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/LinkDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/UnorderedListDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/NumberedListDataTransformer.php`
- `src/Application/DataTransformer/Apps/Body/Trait/LinksDataTransformer.php`
- `src/Application/DataTransformer/Apps/Media/MediaDataTransformer.php` (interface)
- `src/Application/DataTransformer/Apps/Media/MediaDataTransformerHandler.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/DetailsMultimediaPhotoDataTransformer.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/DetailsMultimediaEmbedVideoDataTransformer.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/DetailsMultimediaWidgetDataTransformer.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/Widget/DataTransformerHandler.php` (interface)
- `src/Application/DataTransformer/Apps/Media/DataTransformers/Widget/DetailWidgetDataTransformerHandler.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/Widget/Details/WidgetTypeDataTransformer.php`
- `src/Application/DataTransformer/Apps/Media/DataTransformers/Widget/Details/HtmlWidgetDataTransformer.php`
- `src/Infrastructure/Trait/MultimediaTrait.php`
- `src/Infrastructure/Trait/UrlGeneratorTrait.php`

### Compiler passes reemplazados:
- `src/DependencyInjection/Compiler/BodyDataTransformerCompiler.php`
- `src/DependencyInjection/Compiler/MediaDataTransformerCompiler.php`

### Tests correspondientes a eliminar:
- Todos los tests en `tests/Application/DataTransformer/` (reemplazados por nuevos tests)
- `tests/DependencyInjection/Compiler/BodyDataTransformerCompilerTest.php`
- `tests/DependencyInjection/Compiler/MediaDataTransformerCompilerTest.php`
- `tests/Infrastructure/Trait/UrlGeneratorTraitTest.php`

---

## Fases de implementacion

### Phase 1: Infrastructure Services (aditivo, sin romper nada)

Extraer traits a servicios inyectables. Codigo nuevo junto al existente.

**Crear:**
1. `src/Infrastructure/Service/LinkExtractor.php`
   - `final readonly class`
   - Metodo: `extract(ElementContentWithLinks $element): array`
   - Logica directa del trait `LinksDataTransformer`

2. `src/Infrastructure/Service/UrlGenerator.php`
   - `final readonly class`
   - Constructor: `string $extension` (bind desde services.yaml)
   - Metodo: `generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string`
   - Logica directa del trait `UrlGeneratorTrait`

3. `src/Infrastructure/Service/MultimediaImageService.php`
   - `final readonly class`
   - Constructor: `Thumbor $thumbor`
   - Metodos: `getMultimediaId(Multimedia): ?MultimediaId`, `getShotsLandscape(MultimediaModel): array`, `getShotsLandscapeFromMedia(array): array`
   - `sizes()` como constante
   - Logica directa del trait `MultimediaTrait`

**Tests:**
4. `tests/Infrastructure/Service/LinkExtractorTest.php`
5. `tests/Infrastructure/Service/UrlGeneratorTest.php`
6. `tests/Infrastructure/Service/MultimediaImageServiceTest.php`

**Verificar:** `make test_unit && make test_stan`

---

### Phase 2: Core Interfaces & DTOs

Crear las interfaces y DTOs del nuevo sistema. Aditivo.

**Crear:**
1. `src/Editorial/Assembler/Apps/Body/BodyElementTransformer.php` (interface)
   ```php
   interface BodyElementTransformer {
       /** @return array<string, mixed> */
       public function transform(BodyElement $element, BodyTransformContext $context): array;
       /** @return class-string<BodyElement> */
       public function supports(): string;
   }
   ```
   **GAP 2**: Cada transformer recibe un `BodyTransformContext` tipado en vez de `array $resolveData`.
   Transformers simples (Paragraph, SubHead, Link, etc.) ignoran `$context`.
   Transformers que necesitan datos extra acceden a propiedades tipadas:
   - `BodyTagPictureTransformer` → `$context->bodyPhotos[$photoId]`
   - `BodyTagInsertedNewsTransformer` → `$context->insertedNews[$editorialId]` + `$context->multimedia[$multimediaId]`
   - `BodyTagMembershipCardTransformer` → `$context->membershipLinks`

2. `src/Editorial/Assembler/Apps/Media/MediaTransformer.php` (interface)
   ```php
   interface MediaTransformer {
       /** @return array<string, mixed> */
       public function transform(array $multimediaData, Opening $opening): array;
       /** @return class-string */
       public function supports(): string;
   }
   ```

3. `src/Editorial/Assembler/Apps/Media/Widget/WidgetTypeTransformer.php` (interface)
   ```php
   interface WidgetTypeTransformer {
       /** @return array<string, mixed> */
       public function transform(Widget $widget): array;
       public function supports(): string;
   }
   ```

4. `src/Editorial/EditorialAggregate.php`
   ```php
   final readonly class EditorialAggregate
   {
       /**
        * @param array<Tag>                         $tags
        * @param array<string, mixed>               $signatures       // journalist data per aliasId
        * @param array<string, AbstractMultimedia>   $multimedia       // settled multimedia indexed by ID
        * @param array<string, array{opening: MultimediaPhoto, resource: Photo}> $multimediaOpening
        * @param array<string, Photo>               $bodyPhotos       // body tag photos indexed by ID
        * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $insertedNews
        * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
        * @param array<Editorial>                   $recommendedNews  // raw editorial objects
        * @param array<string, mixed>               $membershipLinks  // resolved membership URL mapping
        */
       public function __construct(
           public Editorial $editorial,
           public Section $section,
           public array $tags,
           public array $signatures,
           public array $multimedia,
           public array $multimediaOpening,
           public array $bodyPhotos,
           public array $insertedNews,
           public array $recommendedEditorials,
           public array $recommendedNews,
           public array $membershipLinks,
           public int $commentCount,
       ) {}
   }
   ```

5. `src/Editorial/BodyTransformContext.php` (**GAP 2 resuelto**: reemplaza `array $resolveData`)
   ```php
   /**
    * Typed context that body element transformers receive.
    * Replaces the untyped array $resolveData bag.
    * Constructed once from EditorialAggregate and passed through the pipeline.
    */
   final readonly class BodyTransformContext
   {
       /**
        * @param array<string, AbstractMultimedia>   $multimedia
        * @param array<string, array{opening: MultimediaPhoto, resource: Photo}> $multimediaOpening
        * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $insertedNews
        * @param array<string, array{editorial: Editorial, section: Section, signatures: array, multimediaId: string}> $recommendedEditorials
        * @param array<string, Photo>               $bodyPhotos       // indexed by photo ID
        * @param array<string, mixed>               $membershipLinks
        */
       public function __construct(
           public array $multimedia,
           public array $multimediaOpening,
           public array $insertedNews,
           public array $recommendedEditorials,
           public array $bodyPhotos,
           public array $membershipLinks,
       ) {}

       /** Factory: builds from the aggregate after resolve phase */
       public static function fromAggregate(EditorialAggregate $agg): self
       {
           return new self(
               multimedia: $agg->multimedia,
               multimediaOpening: $agg->multimediaOpening,
               insertedNews: $agg->insertedNews,
               recommendedEditorials: $agg->recommendedEditorials,
               bodyPhotos: $agg->bodyPhotos,
               membershipLinks: $agg->membershipLinks,
           );
       }
   }
   ```

6. `src/Editorial/EditorialGroup.php`
   ```php
   /** Lightweight DTO for inserted/recommended editorials */
   final readonly class EditorialGroup
   {
       public function __construct(
           public Editorial $editorial,
           public Section $section,
           /** @var array<string, mixed> */
           public array $signatures,
           public string $multimediaId,
       ) {}
   }
   ```

7. `src/Editorial/Resolver/EditorialResolverInterface.php` (interface)
   ```php
   interface EditorialResolverInterface {
       public function resolve(Editorial $editorial, Section $section): void;
   }
   ```
   Nota: los resolvers escriben en un AggregateBuilder mutable, no retornan valores. Esto permite ejecucion flexible sin acoplamiento de tipos de retorno.

8. `src/Editorial/Assembler/ResponseAssemblerInterface.php`
   ```php
   interface ResponseAssemblerInterface {
       /** @return array<string, mixed> */
       public function assemble(EditorialAggregate $aggregate): array;
   }
   ```

**Verificar:** `make test_stan`

---

### Phase 3: Body Element Transformers (stateless)

Reescribir los 15 body element transformers + pipeline. Cada uno es `final readonly class` que implementa `BodyElementTransformer`.

**Crear:**

1. `src/Editorial/Assembler/Apps/Body/BodyTransformerPipeline.php`
   - Reemplaza `BodyDataTransformer` + `BodyElementDataTransformerHandler` (2 clases → 1)
   - Registry: `array<class-string, BodyElementTransformer>`
   - Metodo `addTransformer(BodyElementTransformer)` (para compiler pass)
   - Metodo `transformBody(Body $body, BodyTransformContext $context): array` (itera elementos, despacha con context tipado, captura exceptions)

2. `src/Editorial/Assembler/Apps/Body/ParagraphTransformer.php`
   - Inyecta `LinkExtractor`
   - `transform()`: retorna type + content + links

3. `src/Editorial/Assembler/Apps/Body/SubHeadTransformer.php`
   - Inyecta `LinkExtractor`
   - Identico a Paragraph pero supports() = SubHead::class

4. `src/Editorial/Assembler/Apps/Body/BodyTagHtmlTransformer.php`
   - Sin dependencias extra
   - `transform()`: retorna type + content

5. `src/Editorial/Assembler/Apps/Body/BodyTagSummaryTransformer.php`
   - Sin dependencias extra
   - `transform()`: retorna type + content

6. `src/Editorial/Assembler/Apps/Body/LinkTransformer.php`
   - Sin dependencias extra
   - `transform()`: retorna type + content + url + target

7. `src/Editorial/Assembler/Apps/Body/BodyTagExplanatorySummaryTransformer.php`
   - Inyecta `BodyTransformerPipeline` (composicion recursiva)
   - `transform()`: retorna type + title + items (del body recursivo)

8. `src/Editorial/Assembler/Apps/Body/BodyTagPictureTransformer.php`
   - Inyecta `PictureShots`
   - `transform()`: retorna type + shots + url + caption + alternate + orientation

9. `src/Editorial/Assembler/Apps/Body/BodyTagPictureMembershipTransformer.php`
   - Inyecta `PictureShots`
   - `transform()`: retorna type + shots + url (sin caption/alternate)

10. `src/Editorial/Assembler/Apps/Body/BodyTagVideoTransformer.php`
    - Constructor: `string $playerHost`
    - `transform()`: retorna type + id + width + height + caption + video URL

11. `src/Editorial/Assembler/Apps/Body/BodyTagVideoYoutubeTransformer.php`
    - Constructor: `string $playerHost`
    - `transform()`: retorna type + id + width + height + caption + video URL + start

12. `src/Editorial/Assembler/Apps/Body/BodyTagMembershipCardTransformer.php`
    - Inyecta `BodyTransformerPipeline` (para la picture interna)
    - `transform()`: retorna type + title + buttons + picture

13. `src/Editorial/Assembler/Apps/Body/BodyTagInsertedNewsTransformer.php`
    - Inyecta `UrlGenerator`, `MultimediaImageService`
    - `transform()`: retorna editorial con URL, multimedia, signatures

14. `src/Editorial/Assembler/Apps/Body/UnorderedListTransformer.php`
    - Inyecta `LinkExtractor`
    - `transform()`: retorna type + items (cada item con type + content + links)

15. `src/Editorial/Assembler/Apps/Body/NumberedListTransformer.php`
    - Inyecta `LinkExtractor`
    - Identico a Unordered pero supports() = NumberedList::class

**Tests (1 por transformer + 1 para pipeline):**
16-31. `tests/Editorial/Assembler/Apps/Body/*Test.php`
   - Cada test verifica `transform()` con inputs reales
   - DataProviders para casos edge
   - Patron: crear mock de BodyElement, llamar transform(), assertar array resultado

**Crear compiler pass:**
32. `src/DependencyInjection/Compiler/BodyTransformerCompiler.php`
    - Tag: `app.body_element_transformer`
    - Target: `BodyTransformerPipeline::addTransformer()`

33. `tests/DependencyInjection/Compiler/BodyTransformerCompilerTest.php`

**Verificar:** `make test_unit && make test_stan`

---

### Phase 4: Media Transformers (stateless)

Reescribir los 3 media transformers + widget sub-transformers + pipeline.

**Crear:**

1. `src/Editorial/Assembler/Apps/Media/MediaTransformerPipeline.php`
   - Registry: `array<class-string, MediaTransformer>`
   - Metodo `addTransformer(MediaTransformer)`
   - Metodo `transform(array $multimediaData, Opening $opening): array`

2. `src/Editorial/Assembler/Apps/Media/PhotoTransformer.php`
   - Inyecta `MultimediaImageService`, `Thumbor`
   - Logica de SIZES_RELATIONS (aspect ratios), shots generation
   - ~350 LOC de logica real, mantenida intacta

3. `src/Editorial/Assembler/Apps/Media/EmbedVideoTransformer.php`
   - Sin dependencias extra
   - Regex DailyMotion, fallback generic embed

4. `src/Editorial/Assembler/Apps/Media/WidgetTransformer.php`
   - Inyecta `WidgetTransformerPipeline`
   - Delega a widget-specific transformers

5. `src/Editorial/Assembler/Apps/Media/Widget/WidgetTransformerPipeline.php`
   - Registry para WidgetTypeTransformer

6. `src/Editorial/Assembler/Apps/Media/Widget/HtmlWidgetTransformer.php`
   - `transform(Widget)`: aspect ratio calculation, URL extraction

**Compiler pass:**
7. `src/DependencyInjection/Compiler/MediaTransformerCompiler.php`
   - Tag: `app.media_transformer`
   - Target: `MediaTransformerPipeline::addTransformer()`

8. Modificar `src/DependencyInjection/Compiler/WidgetDataTransformerCompiler.php`
   - Target: nuevo `WidgetTransformerPipeline`

**Tests:**
9-15. Tests para cada transformer + pipelines + compiler pass

**Verificar:** `make test_unit && make test_stan`

---

### Phase 5: Assemblers (replaces top-level transformers)

Crear assemblers que reemplazan los DataTransformers de nivel superior. Cada uno toma datos del aggregate y produce un fragmento del response.

**Crear:**

1. `src/Editorial/Assembler/Apps/EditorialMetadataAssembler.php`
   - Inyecta `UrlGenerator`
   - `assemble(Editorial, Section): array` → id, url, titles, dates, type, flags, countWords
   - Logica extraida de `DetailsAppsDataTransformer::transformerEditorial()`

2. `src/Editorial/Assembler/Apps/SectionAssembler.php`
   - Inyecta `UrlGenerator`
   - `assembleSection(Section): array` → id, name, url, encodeName
   - `assembleOptions(Section): array` → recursive hierarchy
   - Logica extraida de `DetailsAppsDataTransformer::transformerSection()` + `transformerOptions()`

3. `src/Editorial/Assembler/Apps/TagsAssembler.php`
   - Inyecta `UrlGenerator`
   - `assemble(array $tags, Section $section): array`
   - Logica extraida de `DetailsAppsDataTransformer::transformerTags()`

4. `src/Editorial/Assembler/Apps/SignaturesAssembler.php`
   - Inyecta `UrlGenerator`, `Thumbor`
   - `assemble(string $aliasId, Journalist $journalist, Section $section, bool $hasTwitter): array`
   - Logica extraida de `JournalistsDataTransformer`

5. `src/Editorial/Assembler/Apps/MultimediaAssembler.php`
   - Inyecta `MediaTransformerPipeline`, `MultimediaImageService`, `Thumbor`
   - `assembleOpening(array, Opening): ?array` (via media pipeline)
   - `assembleEditorial(array, MultimediaEditorial): ?array` (via editorial multimedia transformer)
   - Logica extraida de `EditorialOrchestrator::transformMultimedia()` + `DetailsMultimediaDataTransformer`

6. `src/Editorial/Assembler/Apps/StandfirstAssembler.php`
   - Inyecta `BodyTransformerPipeline`
   - `assemble(Standfirst): array`
   - Logica extraida de `StandfirstDataTransformer`

7. `src/Editorial/Assembler/Apps/RecommendedAssembler.php`
   - Inyecta `UrlGenerator`, `MultimediaImageService`
   - `assemble(array $recommendedNews, array $resolveData): array`
   - Logica extraida de `RecommendedEditorialsDataTransformer`

8. `src/Editorial/Assembler/Apps/AppsAssembler.php`
   - Implementa `ResponseAssemblerInterface`
   - Inyecta todos los sub-assemblers + `BodyTransformerPipeline`
   - `assemble(EditorialAggregate): array` → compone todos los sub-assemblers
   - Construye `BodyTransformContext::fromAggregate($aggregate)` y lo pasa al body pipeline

**Tests:**
9-16. Tests para cada assembler

**Verificar:** `make test_unit && make test_stan`

---

### Phase 6: Resolvers

Extraer la logica de data fetching de `EditorialOrchestrator` a resolvers individuales.

**GAP 3 resuelto: Patron async de multimedia encapsulado en resolvers.**
El patron actual usa `Utils::settle()` + callback `fulfilledMultimedia()` disperso en el orchestrator.
El nuevo diseno encapsula toda la logica async dentro de `MultimediaResolver`:

```
MultimediaResolver::startAsync(multimediaIds[])     → array<string, Promise>
MultimediaResolver::settle(array<string, Promise>)  → array<string, AbstractMultimedia>
```

Los resolvers que necesitan multimedia (InsertedNews, Recommended) usan MultimediaResolver
internamente para crear y resolver sus propias promises. El ResolverPipeline recopila
TODAS las promises de todos los resolvers y las resuelve en un solo `Utils::settle()` batch
al final, minimizando latencia.

**Crear:**

1. `src/Editorial/Resolver/SectionResolver.php`
   - Inyecta `QuerySectionClient`
   - `resolve(Editorial): Section`

2. `src/Editorial/Resolver/TagsResolver.php`
   - Inyecta `QueryTagClient`, `LoggerInterface`
   - `resolve(Editorial): array<Tag>`

3. `src/Editorial/Resolver/SignaturesResolver.php`
   - Inyecta `QueryJournalistClient`, `JournalistFactory`, `LoggerInterface`
   - `resolve(Editorial, Section): array` (journalist data per signature)

4. `src/Editorial/Resolver/MultimediaResolver.php` (**GAP 3: encapsula patron async**)
   - Inyecta `QueryMultimediaClient`
   - `startAsync(array<string> $multimediaIds): array<string, Promise>` — crea promises sin resolver
   - `settle(array<string, Promise> $promises): array<string, AbstractMultimedia>` — `Utils::settle()` + filtra fulfilled
   - `resolveForEditorial(Editorial $editorial): array<string, AbstractMultimedia>` — shortcut: start + settle
   - Toda la logica de `Utils::settle()` + callback `fulfilledMultimedia()` queda AQUI, no en el pipeline

5. `src/Editorial/Resolver/OpeningResolver.php`
   - Inyecta `QueryMultimediaOpeningClient`, `MultimediaOrchestratorHandler`, `LoggerInterface`
   - `resolve(Editorial): array` (opening multimedia data)
   - Este es sincrono (no usa promises), igual que en el orchestrator actual

6. `src/Editorial/Resolver/BodyPhotosResolver.php`
   - Inyecta `QueryMultimediaClient`, `LoggerInterface`
   - `resolve(Body): array<string, Photo>` (photos indexed by ID for body tag pictures + membership cards)

7. `src/Editorial/Resolver/InsertedNewsResolver.php`
   - Inyecta `QueryEditorialClient`, `QuerySectionClient`, `SignaturesResolver`, `MultimediaResolver`, `LoggerInterface`
   - `resolve(Editorial): array{groups: array<string, EditorialGroup>, promises: array<string, Promise>}`
   - Retorna los EditorialGroup Y las promises de multimedia sin resolver
   - El ResolverPipeline resuelve todas las promises juntas en batch

8. `src/Editorial/Resolver/RecommendedResolver.php`
   - Inyecta mismas deps que InsertedNewsResolver
   - `resolve(Editorial): array{groups: array<string, EditorialGroup>, promises: array<string, Promise>, news: array<Editorial>}`
   - Mismo patron: retorna groups + promises sin resolver + raw editorials

9. `src/Editorial/Resolver/MembershipResolver.php`
   - Inyecta `QueryMembershipClient`, `UriFactoryInterface`
   - `startPromise(Editorial, string $siteId): array{promise: Promise, links: array}`
   - `resolve(Promise, array $links): array<string, mixed>` (membership link combine)

10. `src/Editorial/Resolver/CommentsResolver.php`
    - Inyecta `QueryLegacyClient`
    - `resolve(string $editorialId): int` (comment count)

11. `src/Editorial/Resolver/ResolverPipeline.php`
    - Inyecta todos los resolvers + `MultimediaResolver`
    - `resolve(Editorial): EditorialAggregate`
    - **Orquestacion con batch async:**
      ```
      1. section = SectionResolver::resolve(editorial)        // sincrono, necesario primero
      2. En paralelo conceptual, recopilar promises:
         - tags = TagsResolver::resolve(editorial)
         - signatures = SignaturesResolver::resolve(editorial, section)
         - mainPromises = MultimediaResolver::startAsync([editorial.multimediaId])
         - opening = OpeningResolver::resolve(editorial)
         - bodyPhotos = BodyPhotosResolver::resolve(editorial.body)
         - insertedResult = InsertedNewsResolver::resolve(editorial)
         - recommendedResult = RecommendedResolver::resolve(editorial)
         - membershipResult = MembershipResolver::startPromise(editorial, siteId)
         - commentCount = CommentsResolver::resolve(editorial.id)
      3. Merge ALL promises: mainPromises + insertedResult.promises + recommendedResult.promises
      4. multimedia = MultimediaResolver::settle(allPromises)  // UN solo batch settle
      5. membershipLinks = MembershipResolver::resolve(membershipResult.promise, membershipResult.links)
      6. Build EditorialAggregate con todos los datos resueltos
      ```

**Tests:**
12-22. Tests para cada resolver + pipeline

**Verificar:** `make test_unit && make test_stan`

---

### Phase 7: Integration (conectar todo)

Crear el handler central y conectar con el controller.

**Crear/Modificar:**

1. `src/Editorial/GetEditorialHandler.php`
   ```php
   final readonly class GetEditorialHandler {
       public function __construct(
           private QueryEditorialClient $queryEditorialClient,
           private QueryLegacyClient $queryLegacyClient,
           private ResolverPipeline $resolverPipeline,
           private ResponseAssemblerInterface $assembler,
       ) {}

       public function handle(string $editorialId): array {
           $editorial = $this->queryEditorialClient->findEditorialById($editorialId);
           if (null === $editorial->sourceEditorial()) {
               return $this->queryLegacyClient->findEditorialById($editorialId);
           }
           if (!$editorial->isVisible()) {
               throw new EditorialNotPublishedYetException();
           }
           $aggregate = $this->resolverPipeline->resolve($editorial);
           return $this->assembler->assemble($aggregate);
       }
   }
   ```

2. Modificar `src/Orchestrator/Chain/EditorialOrchestrator.php`
   - Thin adapter: extrae ID de Request, delega a `GetEditorialHandler`
   - Constructor solo necesita: `GetEditorialHandler`
   - Mantiene `canOrchestrate(): string` para chain routing

3. Modificar `config/services.yaml`
   - Registrar namespace `App\Editorial\` con resource scan
   - Excluir body/media transformers del autoscan (registrados via compiler pass)
   - Configurar binds necesarios

4. Modificar `src/Kernel.php`
   - Registrar `BodyTransformerCompiler`
   - Registrar `MediaTransformerCompiler`
   - Remover `BodyDataTransformerCompiler`
   - Remover `MediaDataTransformerCompiler`

5. Modificar `src/Controller/V1/EditorialController.php`
   - Sin cambios si sigue usando `OrchestratorChain` (el chain enruta a EditorialOrchestrator que ahora delega)

**Tests:**
6. `tests/Editorial/GetEditorialHandlerTest.php`
7. Actualizar `tests/Orchestrator/Chain/EditorialOrchestratorTest.php`
8. Actualizar `tests/Controller/V1/EditorialControllerTest.php` (si necesario)
9. Actualizar `tests/KernelTest.php`

**Verificar:** `make test_unit && make test_stan`

---

### Phase 8: Cleanup

Eliminar todo el codigo viejo.

1. Eliminar todos los archivos listados en la seccion "Archivos a ELIMINAR"
2. Eliminar tests correspondientes
3. Eliminar directorios vacios: `src/Application/DataTransformer/`, `src/Infrastructure/Trait/`
4. Verificar que no quedan referencias a clases eliminadas

**Verificar:** `make tests` (full suite: CS, YAML, container, unit, stan, infection)

---

## Principios de diseno transversales

### Stateless transformers
- `transform(input, context) → output`. Sin `write()`/`read()`. Sin estado mutable.
- Cada transformer es `final readonly class`.
- Dependencias inyectadas via constructor.

### Zero herencia en transformers
- Todos implementan interface directamente. 0 clases abstractas.
- Codigo compartido (links, URLs, multimedia) via servicios inyectados.
- Si 2 transformers comparten logica, se extrae a un servicio, no a un padre.

### Traits → Servicios
- `LinksDataTransformer` trait → `LinkExtractor` service
- `UrlGeneratorTrait` → `UrlGenerator` service
- `MultimediaTrait` → `MultimediaImageService` service

### Generic registry pattern
- `BodyTransformerPipeline` y `MediaTransformerPipeline` usan el mismo patron:
  - `addTransformer(T)` registra por key
  - `transform(...)` despacha por `\get_class()`
  - Throws exception si no encontrado
- No es un handler generico compartido (cada pipeline tiene su interfaz y tipado propios).

### DTOs inmutables
- `EditorialAggregate`, `BodyTransformContext` y `EditorialGroup` son `final readonly class`.
- Constructor promotion. Sin setters. Sin estado mutable.

### Testing
- Cada clase nueva tiene su test.
- Tests usan DataProviders donde hay multiples escenarios.
- Mocks via PHPUnit `createMock()`.
- Ningun test depende de herencia (no mas `parent::setUp()`).

---

## Decisiones de arquitectura (Gaps resueltos)

### GAP 1: EditorialAggregate con campos tipados exactos
**Problema**: Los campos del aggregate estaban listados sin tipos, dejando ambiguo el contrato entre Resolve y Assemble.
**Solucion**: `EditorialAggregate` define 12 campos con PHPDoc types exactos (ver Phase 2, item 4). Cada campo mapea 1:1 con lo que el orchestrator actual pasa a los transformers. Los assemblers reciben datos fuertemente tipados — no mas arrays anonimos.

### GAP 2: BodyTransformContext reemplaza `array $resolveData`
**Problema**: Los body transformers actuales reciben `array $resolveData` — un bag sin tipo donde se accede a keys magicas (`$resolveData['photoFromBodyTags']`, `$resolveData['insertedNews']`, etc.). Esto rompe type safety y es fragil ante cambios.
**Solucion**: `BodyTransformContext` es un DTO `final readonly` con propiedades tipadas. Se construye via `BodyTransformContext::fromAggregate()` una sola vez al iniciar el body pipeline. Cada body transformer recibe `BodyTransformContext $context` en su `transform()`. Transformers simples (Paragraph, SubHead) ignoran el context. Transformers complejos acceden propiedades tipadas: `$context->bodyPhotos[$id]`, `$context->insertedNews[$id]`, etc.

### GAP 3: Patron async encapsulado en MultimediaResolver
**Problema**: La logica de promises async (crear promise → Utils::settle() → filtrar fulfilled) estaba dispersa en `EditorialOrchestrator` con callbacks como `fulfilledMultimedia()`. Moverlo a resolvers individuales duplicaria el settle.
**Solucion**: `MultimediaResolver` encapsula el patron completo: `startAsync()` crea promises, `settle()` las resuelve en batch. Los resolvers que necesitan multimedia (InsertedNews, Recommended) retornan sus promises sin resolver. El `ResolverPipeline` merge TODAS las promises y ejecuta UN solo `MultimediaResolver::settle()` al final. Esto mantiene el mismo rendimiento async del orchestrator actual pero con responsabilidades separadas.
