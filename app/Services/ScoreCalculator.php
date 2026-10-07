<?php

namespace App\Services;

use App\Data\ScoreAward;
use InvalidArgumentException;

class ScoreCalculator
{
    /**
     * @var array{
     *     points_per_word: int,
     *     completion_bonus: int,
     *     speed_bonus_tiers: list<array{up_to_seconds: int, points: int}>
     * }
     */
    private readonly array $configuration;

    /**
     * @param  array<string, mixed>|null  $configuration
     */
    public function __construct(?array $configuration = null)
    {
        $configuredScoring = $configuration ?? config('denarius.scoring');

        if (! is_array($configuredScoring)) {
            throw new InvalidArgumentException('A configuração de pontuação deve ser um array.');
        }

        $this->configuration = $this->validatedConfiguration($configuredScoring);
    }

    /**
     * @return array{
     *     points_per_word: int,
     *     completion_bonus: int,
     *     speed_bonus_tiers: list<array{up_to_seconds: int, points: int}>
     * }
     */
    public function configuration(): array
    {
        return $this->configuration;
    }

    public function wordPoints(int $wordCount = 1): int
    {
        if ($wordCount < 0) {
            throw new InvalidArgumentException('A quantidade de palavras não pode ser negativa.');
        }

        return $wordCount * $this->configuration['points_per_word'];
    }

    public function completionBonus(): int
    {
        return $this->configuration['completion_bonus'];
    }

    public function speedBonus(int $durationSeconds): int
    {
        if ($durationSeconds < 0) {
            throw new InvalidArgumentException('A duração não pode ser negativa.');
        }

        foreach ($this->configuration['speed_bonus_tiers'] as $tier) {
            if ($durationSeconds <= $tier['up_to_seconds']) {
                return $tier['points'];
            }
        }

        return 0;
    }

    public function awardForFoundWord(bool $completesSession, ?int $durationSeconds = null): ScoreAward
    {
        if ($completesSession && $durationSeconds === null) {
            throw new InvalidArgumentException('A duração oficial é obrigatória para calcular os bônus finais.');
        }

        return new ScoreAward(
            wordPoints: $this->wordPoints(),
            completionBonus: $completesSession ? $this->completionBonus() : 0,
            speedBonus: $completesSession ? $this->speedBonus($durationSeconds) : 0,
        );
    }

    public function maximumScore(int $totalWords): int
    {
        return $this->wordPoints($totalWords)
            + $this->completionBonus()
            + max(array_column($this->configuration['speed_bonus_tiers'], 'points'));
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @return array{
     *     points_per_word: int,
     *     completion_bonus: int,
     *     speed_bonus_tiers: list<array{up_to_seconds: int, points: int}>
     * }
     */
    private function validatedConfiguration(array $configuration): array
    {
        $pointsPerWord = $configuration['points_per_word'] ?? null;
        $completionBonus = $configuration['completion_bonus'] ?? null;
        $tiers = $configuration['speed_bonus_tiers'] ?? null;

        if (! is_int($pointsPerWord) || $pointsPerWord < 0) {
            throw new InvalidArgumentException('points_per_word deve ser um inteiro não negativo.');
        }

        if (! is_int($completionBonus) || $completionBonus < 0) {
            throw new InvalidArgumentException('completion_bonus deve ser um inteiro não negativo.');
        }

        if (! is_array($tiers) || $tiers === []) {
            throw new InvalidArgumentException('speed_bonus_tiers deve possuir ao menos uma faixa.');
        }

        $validatedTiers = [];
        $previousLimit = -1;

        foreach ($tiers as $tier) {
            if (
                ! is_array($tier)
                || ! is_int($tier['up_to_seconds'] ?? null)
                || ! is_int($tier['points'] ?? null)
                || $tier['up_to_seconds'] <= $previousLimit
                || $tier['points'] < 0
            ) {
                throw new InvalidArgumentException(
                    'As faixas de bônus devem ter limites crescentes e valores inteiros não negativos.',
                );
            }

            $validatedTiers[] = [
                'up_to_seconds' => $tier['up_to_seconds'],
                'points' => $tier['points'],
            ];
            $previousLimit = $tier['up_to_seconds'];
        }

        return [
            'points_per_word' => $pointsPerWord,
            'completion_bonus' => $completionBonus,
            'speed_bonus_tiers' => $validatedTiers,
        ];
    }
}
