<?php

declare(strict_types=1);

namespace App\Service;

use App\Response\GeneratedFlashcardResponse;

/**
 * Mock implementation of flashcard generator.
 * This will be replaced with OpenAI implementation later.
 */
final class MockFlashcardGenerator implements FlashcardGeneratorInterface
{
    public function generate(string $sourceText): array
    {
        // Generate between 3-10 flashcards based on text length
        $wordCount = str_word_count($sourceText);
        $flashcardCount = min(10, max(3, intdiv($wordCount, 30)));
        
        // Detect language (simple heuristic - check for Polish characters)
        $isPolish = $this->detectPolish($sourceText);
        
        $flashcards = [];
        
        // Generate vocabulary flashcards based on detected language
        if ($isPolish) {
            // Polish → English translations
            $templates = [
                ['question' => 'doświadczenie', 'answer' => 'experience'],
                ['question' => 'rozwiązanie', 'answer' => 'solution'],
                ['question' => 'możliwość', 'answer' => 'possibility, opportunity'],
                ['question' => 'w międzyczasie', 'answer' => 'in the meantime'],
                ['question' => 'przeprowadzić', 'answer' => 'to conduct, to carry out'],
                ['question' => 'zwiększyć', 'answer' => 'to increase'],
                ['question' => 'zmniejszyć', 'answer' => 'to decrease, to reduce'],
                ['question' => 'wpływ', 'answer' => 'influence, impact'],
                ['question' => 'osiągnąć', 'answer' => 'to achieve, to accomplish'],
                ['question' => 'zastosowanie', 'answer' => 'application, use'],
            ];
        } else {
            // English → Polish translations
            $templates = [
                ['question' => 'nevertheless', 'answer' => 'mimo to, jednak'],
                ['question' => 'furthermore', 'answer' => 'ponadto, co więcej'],
                ['question' => 'to accomplish', 'answer' => 'osiągnąć, zrealizować'],
                ['question' => 'to enhance', 'answer' => 'zwiększyć, ulepszyć'],
                ['question' => 'essential', 'answer' => 'niezbędny, kluczowy'],
                ['question' => 'approach', 'answer' => 'podejście, sposób'],
                ['question' => 'therefore', 'answer' => 'dlatego, zatem'],
                ['question' => 'significant', 'answer' => 'znaczący, istotny'],
                ['question' => 'opportunity', 'answer' => 'okazja, możliwość'],
                ['question' => 'to implement', 'answer' => 'wdrożyć, wprowadzić'],
            ];
        }
        
        // Shuffle and take only needed amount
        shuffle($templates);
        $selectedTemplates = array_slice($templates, 0, $flashcardCount);
        
        foreach ($selectedTemplates as $template) {
            $flashcards[] = new GeneratedFlashcardResponse(
                $template['question'],
                $template['answer']
            );
        }
        
        return $flashcards;
    }
    
    private function detectPolish(string $text): bool
    {
        // Check for Polish-specific characters
        $polishChars = ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż'];
        $lowerText = mb_strtolower($text);
        
        foreach ($polishChars as $char) {
            if (mb_strpos($lowerText, $char) !== false) {
                return true;
            }
        }
        
        // Check for common Polish words
        $polishWords = ['jest', 'być', 'może', 'można', 'przez', 'oraz', 'także', 'który'];
        foreach ($polishWords as $word) {
            if (mb_strpos($lowerText, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function extractFirstWords(string $text, int $count): string
    {
        $words = explode(' ', $text);
        return implode(' ', array_slice($words, 0, min($count, count($words))));
    }
    
    private function extractKeywords(string $text, int $count): string
    {
        $words = explode(' ', $text);
        // Get random words as "keywords"
        $keywords = array_slice($words, 0, min($count * 3, count($words)));
        $keywords = array_filter($keywords, fn($w) => strlen($w) > 4);
        $keywords = array_slice($keywords, 0, $count);
        
        return implode(', ', $keywords);
    }
}

