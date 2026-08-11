<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\Column;

class EmailColumn extends Column
{
    protected string $view = 'filament.tables.columns.email-column';

    protected bool $showCopyButton = true;

    protected string $copyIcon = 'heroicon-o-clipboard-document';

    protected string $copiedIcon = 'heroicon-o-check';

    public function showCopyButton(bool $condition = true): static
    {
        $this->showCopyButton = $condition;

        return $this;
    }

    public function isCopyButtonVisible(): bool
    {
        return $this->showCopyButton;
    }

    public function copyIcon(string $icon): static
    {
        $this->copyIcon = $icon;

        return $this;
    }

    public function getCopyIcon(): string
    {
        return $this->copyIcon;
    }

    public function copiedIcon(string $icon): static
    {
        $this->copiedIcon = $icon;

        return $this;
    }

    public function getCopiedIcon(): string
    {
        return $this->copiedIcon;
    }
}
