<?php

namespace App\Filament\Forms\Components;

use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\View\Htmlable;

class DateRangeFilter extends Field
{
    protected string $view = 'filament.forms.components.date-range-filter';

    protected string|Htmlable|Closure|null $btnLabel = null;

    public function btnLabel(string|Htmlable|Closure|null $label): static
    {
        $this->btnLabel = $label;

        return $this;
    }

    public function getBtnLabel(): string|Htmlable|null
    {
        $base = $this->evaluate($this->btnLabel) ?? $this->getLabel();

        $state = $this->getState() ?? [];
        $from = data_get($state, 'from');
        $to = data_get($state, 'to');

        if (filled($from) || filled($to)) {
            $base = '';
            $fmt = fn (string $d): string => Carbon::parse($d)->format('d/m/Y');
            $range = collect([
                filled($from) ? $fmt($from) : null,
                filled($to) ? $fmt($to) : null,
            ])->filter()->implode(' - ');

            return "{$base} \u{00B7} {$range}";
        }

        return $base;
    }
}
