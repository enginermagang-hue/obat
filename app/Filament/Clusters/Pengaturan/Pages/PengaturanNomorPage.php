<?php

namespace App\Filament\Clusters\Pengaturan\Pages;

use App\Filament\Clusters\Pengaturan\PengaturanCluster;
use App\Services\NomorFormatService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Log;

class PengaturanNomorPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $cluster = PengaturanCluster::class;

    public function getView(): string
    {
        return 'filament.pages.pengaturan-nomor-page';
    }

    public function getTitle(): string
    {
        return 'Format Nomor';
    }

    public static function getNavigationLabel(): string
    {
        return 'Format Nomor';
    }

    public ?array $data = [];

    public function mount(): void
    {
        $patterns = [];
        try {
            foreach (NomorFormatService::documents() as $key => $doc) {
                $patterns[$key] = NomorFormatService::getPattern($key);
            }
        } catch (\Throwable $e) {
            Log::error('PengaturanNomorPage mount error: '.$e->getMessage());
            Notification::make()
                ->title('Gagal memuat pengaturan')
                ->danger()
                ->send();
        }

        $this->form->fill(['patterns' => $patterns]);
    }

    public function form(Schema $form): Schema
    {
        $fields = [];

        foreach (NomorFormatService::documents() as $key => $doc) {
            $fields[] = TextInput::make("patterns.{$key}")
                ->label($doc['label'])
                ->helperText(fn ($get) => $this->helperPreview($key, $get("patterns.{$key}")))
                ->live(onBlur: true)
                ->inlineLabel()
                ->extraAttributes(['dir' => 'ltr']);
        }

        return $form
            ->schema($fields)
            ->statePath('data');
    }

    private function helperPreview(string $docKey, ?string $pattern): string
    {
        $pattern = $pattern ?? NomorFormatService::getPattern($docKey);

        if (blank($pattern)) {
            return '';
        }

        if ($docKey === 'opname_stok' && str_contains($pattern, '{PREFIX}')) {
            $previews = [];
            foreach (['OPN', 'STK-AWAL', 'STK-BARU'] as $prefix) {
                $previews[] = NomorFormatService::preview($docKey, 1, ['PREFIX' => $prefix]);
            }

            return 'Contoh: '.implode(' | ', $previews);
        }

        $preview = NomorFormatService::preview($docKey, 1);

        return 'Contoh: '.$preview;
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $patterns = $data['patterns'] ?? [];

        NomorFormatService::setGlobal($patterns);

        Notification::make()
            ->title('Pengaturan format nomor berhasil disimpan')
            ->success()
            ->send();
    }

    public function resetToDefaults(): void
    {
        NomorFormatService::resetToDefaults();

        $patterns = [];
        foreach (NomorFormatService::documents() as $key => $doc) {
            $patterns[$key] = NomorFormatService::getPattern($key);
        }

        $this->form->fill(['patterns' => $patterns]);

        Notification::make()
            ->title('Pengaturan berhasil dikembalikan ke default')
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('manage_pengaturan_nomor') ?? false;
    }
}
