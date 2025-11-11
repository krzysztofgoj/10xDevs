# Adaptacja lekcji REST API do Symfony/PHP - 10x-cards

## Wprowadzenie

Ten dokument adaptuje lekcję o REST API z projektu Astro/TypeScript/Supabase do stacku Symfony 6.4/PHP 8.3/PostgreSQL/Doctrine używanego w projekcie 10x-cards.

## Różnice między oryginalną lekcją a naszym stackiem

| Aspekt | Oryginalna lekcja (Astro) | Nasz projekt (Symfony) |
|--------|---------------------------|------------------------|
| **Backend** | Astro + Supabase | Symfony 6.4 + Doctrine ORM |
| **Baza danych** | Supabase (PostgreSQL as a Service) | PostgreSQL 15 + Doctrine |
| **Typy** | TypeScript (`database.types.ts`) | PHP Entities + DTOs |
| **Client** | Supabase JS Client | Doctrine EntityManager + Repositories |
| **Middleware** | Astro middleware | Symfony Services + Event Listeners |
| **Serializacja** | Automatyczna (Supabase) | Symfony Serializer Component |
| **Autoryzacja** | Supabase RLS | Symfony Security Component |

## 1. Inicjalizacja encji Doctrine (zamiast Supabase Client)

### Oryginalna lekcja: Inicjalizacja Supabase

W oryginalnej lekcji tworzyliśmy:
- `/src/db/supabase.client.ts` - klient Supabase
- `/src/middleware/index.ts` - middleware dodający klienta do kontekstu
- `/src/env.d.ts` - rozszerzenie typów TypeScript

### Adaptacja: Tworzenie encji Doctrine

W naszym projekcie zamiast klienta Supabase tworzymy encje Doctrine na podstawie istniejącej migracji.

**Krok 1: Generowanie encji z migracji**

Mamy już migrację `migrations/Version20251111212844.php` z pełnym schematem. Teraz musimy utworzyć encje Doctrine.

**Struktura plików do utworzenia:**

```
src/
  Entity/
    User.php              # Encja użytkownika (UserInterface)
    Flashcard.php         # Encja fiszki
    FlashcardGeneration.php  # Encja sesji generowania
    RepetitionRecord.php  # Encja rekordu powtórki
  Repository/
    UserRepository.php
    FlashcardRepository.php
    FlashcardGenerationRepository.php
    RepetitionRecordRepository.php
```

**Przykład: Encja User**

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
#[ORM\Index(columns: ['email'], name: 'IDX_USER_EMAIL')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Getters, setters, UserInterface methods...
}
```

**Krok 2: Konfiguracja Doctrine (już gotowa)**

Mamy już skonfigurowany `config/packages/doctrine.yaml` z:
- Połączeniem do PostgreSQL
- Auto-mapping encji z `src/Entity`
- Konwencją nazewnictwa (snake_case)

**Krok 3: Repository Pattern**

Doctrine automatycznie tworzy repozytoria, ale możemy je rozszerzyć o custom queries:

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Flashcard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FlashcardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Flashcard::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
```

## 2. Definiowanie specyfikacji API

### Oryginalna lekcja: Generowanie `api-plan.md`

Proces pozostaje taki sam - używamy promptu do generowania planu API na podstawie:
- Schematu bazy danych (mamy migrację)
- PRD (mamy `.ai/prd.md`)
- User Stories

**Różnica**: Zamiast TypeScript types używamy PHP classes (DTOs, Entities).

**Przykładowy prompt (dostosowany do Symfony):**

```
Na podstawie schematu bazy danych (migracja Doctrine) i PRD, wygeneruj 
szczegółowy plan REST API dla projektu Symfony 6.4.

Uwzględnij:
- Endpointy zgodne z konwencją REST
- Symfony route attributes (#[Route])
- DTOs w src/Request/ i src/Response/
- Walidację przez Symfony Validator
- Serializację przez Symfony Serializer
- Autoryzację przez Symfony Security (session-based)
- Format odpowiedzi JSON
- Obsługę błędów przez Exception Listener
```

## 3. Generowanie typów (DTOs i Command Models)

### Oryginalna lekcja: Generowanie TypeScript DTOs

W oryginalnej lekcji generowaliśmy TypeScript interfaces dla DTOs i Command Models.

### Adaptacja: Generowanie PHP DTOs

**Struktura:**

```
src/
  Request/
    CreateFlashcardRequest.php
    UpdateFlashcardRequest.php
    GenerateFlashcardsRequest.php
    BulkCreateFlashcardsRequest.php
  Response/
    FlashcardResponse.php
    FlashcardGenerationResponse.php
    ErrorResponse.php
    PaginatedResponse.php
```

**Przykład: CreateFlashcardRequest**

```php
<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateFlashcardRequest
{
    #[Assert\NotBlank(message: 'Question cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 10000,
        minMessage: 'Question must be at least {{ limit }} characters',
        maxMessage: 'Question cannot exceed {{ limit }} characters'
    )]
    public string $question;

    #[Assert\NotBlank(message: 'Answer cannot be blank')]
    #[Assert\Length(
        min: 1,
        max: 10000,
        minMessage: 'Answer must be at least {{ limit }} characters',
        maxMessage: 'Answer cannot exceed {{ limit }} characters'
    )]
    public string $answer;

    #[Assert\Choice(choices: ['ai', 'manual'], message: 'Source must be either "ai" or "manual"')]
    public string $source = 'manual';
}
```

**Przykład: FlashcardResponse**

```php
<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\Serializer\Annotation\Groups;

final class FlashcardResponse
{
    #[Groups(['api'])]
    public int $id;

    #[Groups(['api'])]
    public string $question;

    #[Groups(['api'])]
    public string $answer;

    #[Groups(['api'])]
    public string $source;

    #[Groups(['api'])]
    public \DateTimeImmutable $createdAt;

    #[Groups(['api'])]
    public \DateTimeImmutable $updatedAt;

    public function __construct(
        int $id,
        string $question,
        string $answer,
        string $source,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt
    ) {
        $this->id = $id;
        $this->question = $question;
        $this->answer = $answer;
        $this->source = $source;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }
}
```

## 4. Plan implementacji endpointa

### Oryginalna lekcja: Plan dla POST /generations

Proces planowania pozostaje taki sam, ale dostosowujemy do Symfony:

**Przykładowy plan dla POST /api/generations:**

```markdown
# Plan implementacji: POST /api/generations

## Endpoint
POST /api/generations

## Controller
App\Controller\Api\FlashcardGenerationController::create()

## Request
- DTO: `App\Request\GenerateFlashcardsRequest`
- Walidacja: Symfony Validator (min 1000, max 10000 znaków)
- Content-Type: application/json

## Response
- Success (201): `FlashcardGenerationResponse`
- Error (400): Validation errors
- Error (401): Unauthorized
- Error (500): Server error

## Flow
1. Walidacja request body → GenerateFlashcardsRequest
2. Pobranie zalogowanego użytkownika (Security)
3. Utworzenie FlashcardGeneration (status: pending)
4. Wywołanie serwisu AI (FlashcardGenerationService)
5. Zapis wyników do bazy
6. Serializacja odpowiedzi
7. Zwrócenie JSON response

## Security
- Wymagana autoryzacja (ROLE_USER)
- User ID z Security Token
- Izolacja danych (tylko własne generacje)

## Error Handling
- ValidationException → 400
- UnauthorizedException → 401
- AI Service Exception → 500
```

## 5. Workflow 3x3

### Oryginalna lekcja: Workflow 3x3

Workflow pozostaje identyczny - agent wykonuje 3 kroki, raportuje i proponuje kolejne 3.

**Przykład dla Symfony:**

```
<implementation_approach>
Realizuj maksymalnie 3 kroki planu implementacji, podsumuj krótko co zrobiłeś 
i opisz plan na 3 kolejne działania - zatrzymaj w tym momencie pracę i czekaj 
na mój feedback.
</implementation_approach>
```

**Przykładowe kroki dla POST /api/generations:**

1. ✅ Utworzenie `GenerateFlashcardsRequest` DTO z walidacją
2. ✅ Utworzenie `FlashcardGenerationResponse` DTO
3. ✅ Utworzenie kontrolera `FlashcardGenerationController` z metodą `create()`

**Następne 3 kroki:**
4. Utworzenie serwisu `FlashcardGenerationService` z logiką biznesową
5. Integracja z API LLM (abstrakcja `LLMServiceInterface`)
6. Obsługa błędów i testy

## 6. Implementacja endpointa

### Oryginalna lekcja: Implementacja z workflow 3x3

**Przykład kontrolera Symfony:**

```php
<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Request\GenerateFlashcardsRequest;
use App\Response\FlashcardGenerationResponse;
use App\Service\FlashcardGenerationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsController]
#[Route('/api/generations', name: 'api_generations_')]
#[IsGranted('ROLE_USER')]
final class FlashcardGenerationController extends AbstractController
{
    public function __construct(
        private readonly FlashcardGenerationService $generationService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            GenerateFlashcardsRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['errors' => (string) $errors],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        $user = $this->getUser();
        $generation = $this->generationService->generate($dto, $user);

        $response = new FlashcardGenerationResponse(
            $generation->getId(),
            $generation->getStatus(),
            $generation->getFlashcards()
        );

        return new JsonResponse(
            $this->serializer->serialize($response, 'json', ['groups' => ['api']]),
            JsonResponse::HTTP_CREATED,
            [],
            true
        );
    }
}
```

## 7. Szybkie testy endpointa

### Oryginalna lekcja: Generowanie curl

W Symfony możemy użyć:

**1. Symfony Test Client (automatyczne testy):**

```php
<?php

declare(strict_types=1);

namespace App\Tests\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class FlashcardGenerationControllerTest extends WebTestCase
{
    public function testCreateGeneration(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getTestUser());

        $client->request(
            'POST',
            '/api/generations',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'sourceText' => str_repeat('a', 1000) // min 1000 znaków
            ])
        );

        $this->assertResponseStatusCodeSame(201);
    }
}
```

**2. Ręczne testy curl:**

```bash
# Logowanie (uzyskanie sesji)
curl -X POST http://localhost:8080/login \
  -d "email=user@example.com&password=password" \
  -c cookies.txt

# Tworzenie generacji
curl -X POST http://localhost:8080/api/generations \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "sourceText": "Lorem ipsum dolor sit amet..."
  }'
```

## Podsumowanie adaptacji

### Co się zmienia:

1. **Supabase Client → Doctrine Entities + Repositories**
   - Zamiast klienta Supabase używamy Doctrine EntityManager
   - Encje mapują tabele z migracji
   - Repozytoria dla custom queries

2. **TypeScript Types → PHP DTOs**
   - Request DTOs w `src/Request/`
   - Response DTOs w `src/Response/`
   - Walidacja przez Symfony Validator

3. **Astro Middleware → Symfony Services**
   - Logika biznesowa w serwisach
   - Dependency Injection przez konstruktor
   - Event Listeners dla cross-cutting concerns

4. **Automatyczna serializacja → Symfony Serializer**
   - Grupy serializacji (`['groups' => ['api']]`)
   - Normalizacja encji Doctrine
   - Custom normalizers jeśli potrzeba

5. **Supabase RLS → Symfony Security**
   - Autoryzacja przez `#[IsGranted]`
   - Izolacja danych w repozytoriach (filtrowanie po user_id)
   - Session-based auth (można rozszerzyć o JWT)

### Co pozostaje takie samo:

- ✅ Proces planowania API (api-plan.md)
- ✅ Workflow 3x3
- ✅ Generowanie DTOs na podstawie schematu
- ✅ Szczegółowe plany implementacji endpointów
- ✅ Testowanie (curl + automatyczne testy)

## Następne kroki

1. **Odpowiedź na pytania** z `.ai/api-implementation-questions.md`
2. **Utworzenie encji Doctrine** na podstawie migracji
3. **Generowanie planu API** (`api-plan.md`)
4. **Utworzenie struktury DTOs** (Request/Response)
5. **Implementacja pierwszego endpointa** (workflow 3x3)

