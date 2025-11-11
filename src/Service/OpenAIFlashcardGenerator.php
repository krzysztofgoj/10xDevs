<?php

declare(strict_types=1);

namespace App\Service;

use App\Response\GeneratedFlashcardResponse;
use OpenAI\Client;
use Psr\Log\LoggerInterface;

/**
 * OpenAI implementation of flashcard generator.
 * Uses GPT-4 to generate high-quality flashcards from source text.
 */
final class OpenAIFlashcardGenerator implements FlashcardGeneratorInterface
{
    private const MAX_FLASHCARDS = 10;
    private const MIN_FLASHCARDS = 3;
    private const MODEL = 'gpt-4o-mini'; // Tańszy model, nadal dobra jakość
    private const MAX_TOKENS = 2000;
    private const TEMPERATURE = 0.7;

    public function __construct(
        private readonly Client $openAIClient,
        private readonly LoggerInterface $logger
    ) {
    }

    public function generate(string $sourceText): array
    {
        $startTime = microtime(true);
        
        try {
            $this->logger->info('OpenAI: Generating flashcards', [
                'text_length' => strlen($sourceText),
                'word_count' => str_word_count($sourceText),
            ]);

            $prompt = $this->buildPrompt($sourceText);
            
            $response = $this->openAIClient->chat()->create([
                'model' => self::MODEL,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => self::TEMPERATURE,
                'max_tokens' => self::MAX_TOKENS,
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $data = json_decode($content, true);

            if (!$data) {
                throw new \RuntimeException('Failed to decode OpenAI response');
            }

            $flashcards = $this->parseFlashcards($data);
            
            $duration = microtime(true) - $startTime;
            $tokensUsed = $response->usage->totalTokens ?? 0;
            
            $this->logger->info('OpenAI: Flashcards generated successfully', [
                'count' => count($flashcards),
                'duration_seconds' => round($duration, 2),
                'tokens_used' => $tokensUsed,
                'estimated_cost_usd' => $this->estimateCost($tokensUsed),
                'model' => self::MODEL,
            ]);

            return $flashcards;

        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $this->logger->error('OpenAI: Failed to generate flashcards', [
                'error' => $e->getMessage(),
                'duration_seconds' => round($duration, 2),
            ]);

            throw new \RuntimeException(
                'Failed to generate flashcards with AI: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    private function getSystemPrompt(): string
    {
        return <<<SYSTEM
Jesteś ekspertem w tworzeniu fiszek językowych do nauki słówek. Twoim zadaniem jest wyciąganie kluczowych słów, zwrotów i idiomów z tekstu i tworzenie prostych fiszek z tłumaczeniami.

ZASADY:
1. Generuj od 3 do 10 fiszek (w zależności od długości tekstu)
2. WYKRYJ JĘZYK TEKSTU:
   - Jeśli tekst po POLSKU → wyciągaj polskie słowa/zwroty i tłumacz je na ANGIELSKI
   - Jeśli tekst po ANGIELSKU → wyciągaj angielskie słowa/zwroty i tłumacz je na POLSKI
3. Wybieraj:
   - Kluczowe słowa (rzeczowniki, czasowniki, przymiotniki)
   - Przydatne zwroty i wyrażenia
   - Idiomy i kolokacje
   - Wyrazy/zwroty najczęściej występujące w tekście
4. Format fiszki:
   - question: słowo/zwrot w języku ORYGINALNYM (z tekstu)
   - answer: tłumaczenie w języku DOCELOWYM
   - Krótko i zwięźle - to są FISZKI, nie definicje
5. Priorytetyzuj:
   - Najważniejsze i najczęstsze słowa
   - Przydatne wyrażenia
   - Słownictwo użyteczne w codziennym życiu

PRZYKŁADY:
Tekst PL → EN:
{"question": "w międzyczasie", "answer": "in the meantime"}
{"question": "doświadczenie", "answer": "experience"}

Tekst EN → PL:
{"question": "nevertheless", "answer": "mimo to, jednak"}
{"question": "to accomplish", "answer": "osiągnąć, zrealizować"}

FORMAT ODPOWIEDZI:
Zwróć TYLKO poprawny JSON w formacie:
{
  "flashcards": [
    {"question": "słowo/zwrot", "answer": "tłumaczenie"},
    {"question": "słowo/zwrot", "answer": "tłumaczenie"}
  ]
}

NIE dodawaj żadnego tekstu poza JSON.
SYSTEM;
    }

    private function buildPrompt(string $sourceText): string
    {
        $wordCount = str_word_count($sourceText);
        $suggestedCount = min(self::MAX_FLASHCARDS, max(self::MIN_FLASHCARDS, intdiv($wordCount, 30)));

        return <<<PROMPT
Wygeneruj około {$suggestedCount} fiszek językowych (słówka + tłumaczenia) na podstawie poniższego tekstu.

TEKST ŹRÓDŁOWY:
{$sourceText}

ZADANIE:
1. WYKRYJ język tekstu (polski czy angielski)
2. Wyciągnij {$suggestedCount} najważniejszych słów/zwrotów z tekstu
3. Przetłumacz je na drugi język
4. Jeśli tekst PO POLSKU → question = polski, answer = angielski
5. Jeśli tekst PO ANGIELSKU → question = angielski, answer = polski

FORMAT:
- Krótkie fiszki: słowo/zwrot → tłumaczenie
- NIE twórz pytań typu "Co to znaczy...?"
- NIE dodawaj definicji, tylko TŁUMACZENIA
- Wybieraj najbardziej użyteczne słownictwo

Zwróć TYLKO JSON bez dodatkowego tekstu.
PROMPT;
    }

    private function parseFlashcards(array $data): array
    {
        if (!isset($data['flashcards']) || !is_array($data['flashcards'])) {
            $this->logger->error('OpenAI: Invalid response format', ['data' => $data]);
            throw new \RuntimeException('Invalid response format from OpenAI');
        }

        $flashcards = [];

        foreach ($data['flashcards'] as $item) {
            if (!isset($item['question']) || !isset($item['answer'])) {
                $this->logger->warning('OpenAI: Skipping invalid flashcard', ['item' => $item]);
                continue;
            }

            $question = trim($item['question']);
            $answer = trim($item['answer']);

            if (empty($question) || empty($answer)) {
                continue;
            }

            $flashcards[] = new GeneratedFlashcardResponse($question, $answer);
        }

        // Validate count
        $count = count($flashcards);
        if ($count < self::MIN_FLASHCARDS) {
            throw new \RuntimeException("Too few flashcards generated: {$count}");
        }

        // Limit to max
        if ($count > self::MAX_FLASHCARDS) {
            $flashcards = array_slice($flashcards, 0, self::MAX_FLASHCARDS);
        }

        return $flashcards;
    }

    /**
     * Estimate cost in USD based on tokens used.
     * Prices for gpt-4o-mini (as of 2024):
     * - Input: $0.150 / 1M tokens
     * - Output: $0.600 / 1M tokens
     */
    private function estimateCost(int $tokensUsed): float
    {
        // Zakładamy proporcję input:output około 70:30
        $inputTokens = (int)($tokensUsed * 0.7);
        $outputTokens = (int)($tokensUsed * 0.3);

        $inputCost = ($inputTokens / 1_000_000) * 0.150;
        $outputCost = ($outputTokens / 1_000_000) * 0.600;

        return round($inputCost + $outputCost, 6);
    }
}

