<?php

namespace App\Support;

use Filament\Support\Enums\Alignment;
use YousefAman\ModalRepeater\Column;

class ModalRepeaterColumn extends Column
{
    protected ?string $alignment = null;

    public function align(Alignment|string $alignment): static
    {
        $this->alignment = $alignment instanceof Alignment ? $alignment->value : $alignment;

        return $this;
    }

    public function getAlignment(): ?string
    {
        return $this->alignment;
    }
}
