<?php

namespace App\Enums;

enum WordDirection: string
{
    case Right = 'RIGHT';
    case Left = 'LEFT';
    case Down = 'DOWN';
    case Up = 'UP';
    case DownRight = 'DOWN_RIGHT';
    case DownLeft = 'DOWN_LEFT';
    case UpRight = 'UP_RIGHT';
    case UpLeft = 'UP_LEFT';

    public function rowDelta(): int
    {
        return match ($this) {
            self::Right, self::Left => 0,
            self::Down, self::DownRight, self::DownLeft => 1,
            self::Up, self::UpRight, self::UpLeft => -1,
        };
    }

    public function columnDelta(): int
    {
        return match ($this) {
            self::Down, self::Up => 0,
            self::Right, self::DownRight, self::UpRight => 1,
            self::Left, self::DownLeft, self::UpLeft => -1,
        };
    }
}
