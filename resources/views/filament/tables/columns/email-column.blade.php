@php
    $alignment = $column->getAlignment();

    $textAlign = match (true) {
        $alignment instanceof \Filament\Support\Enums\Alignment => match ($alignment) {
            \Filament\Support\Enums\Alignment::Center => 'center',
            \Filament\Support\Enums\Alignment::End,
            \Filament\Support\Enums\Alignment::Right => 'end',
            default => 'start',
        },
        is_string($alignment) => match ($alignment) {
            'center' => 'center',
            'end', 'right' => 'end',
            default => 'start',
        },
        default => 'start',
    };
@endphp

<div class="fi-email-column px-3" style="text-align: {{ $textAlign }}">
    <a href="mailto:{{ $getState() }}"
        class="inline text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 underline underline-offset-2">
        {{ $getState() }}
    </a>

    @if ($column->isCopyButtonVisible())
        <button type="button"
                x-data="{ copied: false }"
                x-on:click="
                    navigator.clipboard.writeText(@js($getState())).then(() => {
                        copied = true;
                        setTimeout(() => copied = false, 1500);
                        $dispatch('notify', { type: 'success', message: 'Email copied!' });
                    });
                "
                x-bind:title="copied ? 'Copied!' : 'Copy email'"
                x-bind:class="copied ? 'text-success-500' : 'text-gray-400'"
            class="inline-flex p-0.5 transition hover:text-gray-600 dark:hover:text-gray-300">
            <span x-show="!copied">
                <x-filament::icon :icon="$column->getCopyIcon()" class="size-4" />
            </span>
            <span x-show="copied">
                <x-filament::icon :icon="$column->getCopiedIcon()" class="size-4" />
            </span>
        </button>
    @endif
</div>