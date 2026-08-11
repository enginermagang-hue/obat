<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.pages.edit-role';

    /** @var list<string> */
    private const EXCLUDED_KEYS = [
        'globalPermissionSearch',
    ];

    /** @var list<string> */
    private array $pendingPermissionNames = [];

    public string $permissionSearch = '';

    public array $selectedPermissions = [];

    public function getGroupedPermissionsProperty(): Collection
    {
        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $p): string => $this->extractResource($p->name))
            ->sortKeys()
            ->filter(function ($group, $resource) {
                if ($this->permissionSearch === '') {
                    return true;
                }
                $searchable = strtolower($resource.' '.$group->pluck('name')->implode(' '));

                return str_contains($searchable, strtolower($this->permissionSearch));
            });
    }

    public function extractResource(string $permission): string
    {
        $parts = explode('_', $permission);
        array_shift($parts);

        return empty($parts)
            ? 'Umum'
            : ucwords(str_replace('_', ' ', implode('_', $parts)));
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('delete')
                ->label('Hapus')
                ->color('danger')
                ->requiresConfirmation()
                ->modalContent(fn (): View => view('filament.resources.roles.pages.delete-role-warning', [
                    'record' => $this->record,
                ]))
                ->action(function (): void {
                    $userCount = $this->record->users()->count();

                    if ($userCount > 0) {
                        Notification::make()
                            ->title('Role tidak dapat dihapus')
                            ->body("Role ini masih digunakan oleh {$userCount} pengguna. Hapus atau ubah role pengguna terlebih dahulu.")
                            ->danger()
                            ->send();

                        return;
                    }

                    $this->record->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingPermissionNames = array_keys(
            array_filter($this->selectedPermissions)
        );

        return array_filter(
            $data,
            fn (mixed $val, string $key): bool => ! str_starts_with($key, 'perm_')
                && ! str_starts_with($key, '_')
                && ! in_array($key, self::EXCLUDED_KEYS, true),
            \ARRAY_FILTER_USE_BOTH,
        );
    }

    protected function afterSave(): void
    {
        if ($this->pendingPermissionNames !== []) {
            $this->record->syncPermissions($this->pendingPermissionNames);
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->record->load('permissions');

        $this->selectedPermissions = [];
        foreach ($this->record->permissions as $permission) {
            $this->selectedPermissions[$permission->name] = true;
        }

        return $data;
    }
}
