<?php

namespace App\Enums;

enum MetodeStok: string
{
    case FEFO = 'fefo';
    case FIFO = 'fifo';
    case LIFO = 'lifo';

    public function getLabel(): string
    {
        return match ($this) {
            self::FEFO => 'FEFO',
            self::FIFO => 'FIFO',
            self::LIFO => 'LIFO',
        };
    }
}
