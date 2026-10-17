<?php

namespace Tests\Unit;

use App\Services\ScoreCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ScoreCalculatorTest extends TestCase
{
    private const CONFIGURATION = [
        'points_per_word' => 100,
        'completion_bonus' => 500,
        'speed_bonus_tiers' => [
            ['up_to_seconds' => 120, 'points' => 500],
            ['up_to_seconds' => 180, 'points' => 300],
            ['up_to_seconds' => 300, 'points' => 150],
        ],
    ];

    public function test_calculates_word_points_for_a_configurable_quantity(): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $this->assertSame(100, $calculator->wordPoints());
        $this->assertSame(400, $calculator->wordPoints(4));
    }

    #[DataProvider('speedBonusCases')]
    public function test_calculates_speed_bonus_at_every_boundary(int $durationSeconds, int $expectedBonus): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $this->assertSame($expectedBonus, $calculator->speedBonus($durationSeconds));
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function speedBonusCases(): array
    {
        return [
            'zero seconds' => [0, 500],
            'two minutes exactly' => [120, 500],
            'after two minutes' => [121, 300],
            'three minutes exactly' => [180, 300],
            'after three minutes' => [181, 150],
            'five minutes exactly' => [300, 150],
            'after five minutes' => [301, 0],
        ];
    }

    public function test_awards_only_word_points_before_completion(): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $award = $calculator->awardForFoundWord(completesSession: false);

        $this->assertSame(100, $award->wordPoints);
        $this->assertSame(0, $award->completionBonus);
        $this->assertSame(0, $award->speedBonus);
        $this->assertSame(100, $award->total());
    }

    public function test_awards_word_completion_and_speed_points_on_completion(): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $award = $calculator->awardForFoundWord(completesSession: true, durationSeconds: 180);

        $this->assertSame(100, $award->wordPoints);
        $this->assertSame(500, $award->completionBonus);
        $this->assertSame(300, $award->speedBonus);
        $this->assertSame(900, $award->total());
    }

    public function test_calculates_predictable_maximum_for_any_word_count(): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $this->assertSame(2000, $calculator->maximumScore(10));
        $this->assertSame(1800, $calculator->maximumScore(8));
    }

    public function test_rejects_completion_without_official_duration(): void
    {
        $calculator = new ScoreCalculator(self::CONFIGURATION);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duração oficial');

        $calculator->awardForFoundWord(completesSession: true);
    }

    public function test_rejects_invalid_configuration(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ScoreCalculator([
            'points_per_word' => -1,
            'completion_bonus' => 500,
            'speed_bonus_tiers' => [['up_to_seconds' => 120, 'points' => 500]],
        ]);
    }
}
