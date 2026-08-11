<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $widgets = collect($this->getWidgets());
            $skipWidgets = [
                App\Filament\Widgets\SelamatDatangWidget::class,
                App\Filament\Widgets\SalesPurchaseChart::class,
                App\Filament\Widgets\OverallInformationWidget::class,
            ];
        @endphp

        @livewire(App\Filament\Widgets\SelamatDatangWidget::class)

        @if ($widgets->contains(App\Filament\Widgets\SalesPurchaseChart::class) && $widgets->contains(App\Filament\Widgets\OverallInformationWidget::class))
            <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    @livewire(App\Filament\Widgets\SalesPurchaseChart::class)
                </div>
                <div class="lg:col-span-2">
                    @livewire(App\Filament\Widgets\OverallInformationWidget::class)
                </div>
            </div>
        @endif

        @foreach ($widgets as $widget)
            @if (! in_array($widget, $skipWidgets))
                @livewire($widget)
            @endif
        @endforeach
    </div>
</x-filament-panels::page>
