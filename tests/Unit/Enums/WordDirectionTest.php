<?php

namespace Tests\Unit\Enums;

use App\Enums\WordDirection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WordDirectionTest extends TestCase
{
    #[DataProvider('directionVectors')]
    public function test_each_direction_has_an_explicit_coordinate_vector(
        WordDirection $direction,
        int $expectedRowDelta,
        int $expectedColumnDelta,
    ): void {
        $this->assertSame($expectedRowDelta, $direction->rowDelta());
        $this->assertSame($expectedColumnDelta, $direction->columnDelta());
    }

    /**
     * @return array<string, array{WordDirection, int, int}>
     */
    public static function directionVectors(): array
    {
        return [
            'horizontal direita' => [WordDirection::Right, 0, 1],
            'horizontal esquerda' => [WordDirection::Left, 0, -1],
            'vertical baixo' => [WordDirection::Down, 1, 0],
            'vertical cima' => [WordDirection::Up, -1, 0],
            'diagonal baixo-direita' => [WordDirection::DownRight, 1, 1],
            'diagonal baixo-esquerda' => [WordDirection::DownLeft, 1, -1],
            'diagonal cima-direita' => [WordDirection::UpRight, -1, 1],
            'diagonal cima-esquerda' => [WordDirection::UpLeft, -1, -1],
        ];
    }
}
