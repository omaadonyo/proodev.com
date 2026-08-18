<?php

namespace App\Services;

class LevelService
{
    /**
     * Level thresholds ordered from lowest to highest experience requirement.
     *
     * @var array<int, array{title: string, xp: int}>
     */
    public const LEVELS = [
        1 => ['title' => 'Explorer', 'xp' => 0],
        2 => ['title' => 'Builder', 'xp' => 150],
        3 => ['title' => 'Engineer', 'xp' => 500],
        4 => ['title' => 'Senior Engineer', 'xp' => 1200],
        5 => ['title' => 'Architect', 'xp' => 2500],
        6 => ['title' => 'Principal Engineer', 'xp' => 5000],
        7 => ['title' => 'Community Mentor', 'xp' => 10000],
    ];

    public function levelForXp(int $xp): int
    {
        $level = 1;

        foreach (self::LEVELS as $number => $definition) {
            if ($xp >= $definition['xp']) {
                $level = $number;
            }
        }

        return $level;
    }

    public function titleForLevel(int $level): string
    {
        return self::LEVELS[$level]['title'] ?? 'Explorer';
    }

    public function thresholdForLevel(int $level): int
    {
        return self::LEVELS[$level]['xp'] ?? 0;
    }

    public function maxLevel(): int
    {
        return max(array_keys(self::LEVELS));
    }

    public function xpToNextLevel(int $xp): int
    {
        $current = $this->levelForXp($xp);
        $next = min($current + 1, $this->maxLevel());

        if ($current === $next) {
            return 0;
        }

        return max(0, $this->thresholdForLevel($next) - $xp);
    }

    public function progress(int $xp): float
    {
        $current = $this->levelForXp($xp);
        $next = min($current + 1, $this->maxLevel());

        $start = $this->thresholdForLevel($current);
        $end = $this->thresholdForLevel($next);

        if ($end <= $start) {
            return 100.0;
        }

        return round(min(100, max(0, ($xp - $start) / ($end - $start) * 100)), 1);
    }

    /**
     * @return array{current: int, next: int, title: string, next_title: string, progress: float, xp_to_next: int, xp: int}
     */
    public function snapshot(int $xp): array
    {
        $current = $this->levelForXp($xp);
        $next = min($current + 1, $this->maxLevel());

        return [
            'current' => $current,
            'next' => $next,
            'title' => $this->titleForLevel($current),
            'next_title' => $this->titleForLevel($next),
            'progress' => $this->progress($xp),
            'xp_to_next' => $this->xpToNextLevel($xp),
            'xp' => $xp,
        ];
    }
}
