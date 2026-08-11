<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected string $view = 'filament.resources.roles.pages.create-role';

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

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if ($this->pendingPermissionNames !== []) {
            $this->record->syncPermissions($this->pendingPermissionNames);
        }
    }
}
