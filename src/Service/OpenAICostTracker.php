<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

/**
 * Tracks OpenAI API usage and costs to prevent overspending.
 */
final class OpenAICostTracker
{
    private const CACHE_KEY_PREFIX = 'openai_cost_';
    private const DAILY_LIMIT_USD = 100.00; // $100 dziennie (premium)
    private const MONTHLY_LIMIT_USD = 1000.00; // $1000 miesięcznie (premium)

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Check if we can make another API call without exceeding budget.
     */
    public function canMakeRequest(): bool
    {
        $dailyUsage = $this->getDailyUsage();
        $monthlyUsage = $this->getMonthlyUsage();

        if ($dailyUsage >= self::DAILY_LIMIT_USD) {
            $this->logger->warning('OpenAI: Daily cost limit reached', [
                'daily_usage_usd' => $dailyUsage,
                'daily_limit_usd' => self::DAILY_LIMIT_USD,
            ]);
            return false;
        }

        if ($monthlyUsage >= self::MONTHLY_LIMIT_USD) {
            $this->logger->warning('OpenAI: Monthly cost limit reached', [
                'monthly_usage_usd' => $monthlyUsage,
                'monthly_limit_usd' => self::MONTHLY_LIMIT_USD,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Record a successful API call with its cost.
     */
    public function recordUsage(float $costUsd, int $tokensUsed): void
    {
        $today = date('Y-m-d');
        $month = date('Y-m');

        // Update daily usage
        $dailyKey = self::CACHE_KEY_PREFIX . 'daily_' . $today;
        $dailyItem = $this->cache->getItem($dailyKey);
        $dailyUsage = $dailyItem->isHit() ? (float)$dailyItem->get() : 0.0;
        $dailyItem->set($dailyUsage + $costUsd);
        $dailyItem->expiresAfter(86400 * 2); // 2 days TTL
        $this->cache->save($dailyItem);

        // Update monthly usage
        $monthlyKey = self::CACHE_KEY_PREFIX . 'monthly_' . $month;
        $monthlyItem = $this->cache->getItem($monthlyKey);
        $monthlyUsage = $monthlyItem->isHit() ? (float)$monthlyItem->get() : 0.0;
        $monthlyItem->set($monthlyUsage + $costUsd);
        $monthlyItem->expiresAfter(86400 * 35); // 35 days TTL
        $this->cache->save($monthlyItem);

        $this->logger->info('OpenAI: Usage recorded', [
            'cost_usd' => $costUsd,
            'tokens' => $tokensUsed,
            'daily_total_usd' => $dailyUsage + $costUsd,
            'monthly_total_usd' => $monthlyUsage + $costUsd,
        ]);
    }

    /**
     * Get current daily usage in USD.
     */
    public function getDailyUsage(): float
    {
        $today = date('Y-m-d');
        $dailyKey = self::CACHE_KEY_PREFIX . 'daily_' . $today;
        $item = $this->cache->getItem($dailyKey);
        
        return $item->isHit() ? (float)$item->get() : 0.0;
    }

    /**
     * Get current monthly usage in USD.
     */
    public function getMonthlyUsage(): float
    {
        $month = date('Y-m');
        $monthlyKey = self::CACHE_KEY_PREFIX . 'monthly_' . $month;
        $item = $this->cache->getItem($monthlyKey);
        
        return $item->isHit() ? (float)$item->get() : 0.0;
    }

    /**
     * Get usage statistics.
     */
    public function getUsageStats(): array
    {
        $dailyUsage = $this->getDailyUsage();
        $monthlyUsage = $this->getMonthlyUsage();

        return [
            'daily' => [
                'used_usd' => round($dailyUsage, 4),
                'limit_usd' => self::DAILY_LIMIT_USD,
                'remaining_usd' => round(self::DAILY_LIMIT_USD - $dailyUsage, 4),
                'percentage_used' => round(($dailyUsage / self::DAILY_LIMIT_USD) * 100, 2),
            ],
            'monthly' => [
                'used_usd' => round($monthlyUsage, 4),
                'limit_usd' => self::MONTHLY_LIMIT_USD,
                'remaining_usd' => round(self::MONTHLY_LIMIT_USD - $monthlyUsage, 4),
                'percentage_used' => round(($monthlyUsage / self::MONTHLY_LIMIT_USD) * 100, 2),
            ],
        ];
    }

    /**
     * Reset usage counters (for testing).
     */
    public function resetUsage(): void
    {
        $today = date('Y-m-d');
        $month = date('Y-m');

        $this->cache->deleteItem(self::CACHE_KEY_PREFIX . 'daily_' . $today);
        $this->cache->deleteItem(self::CACHE_KEY_PREFIX . 'monthly_' . $month);

        $this->logger->info('OpenAI: Usage counters reset');
    }
}

