<x-filament-panels::page>
    <style>
        .dark .perm-search-input { border-color: var(--color-gray-600) !important; }
    </style>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="px-6 pt-6 pb-2">
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Hak Akses (Permissions)
                </h2>
            </div>

            <div class="px-6 pb-4">
                <div class="relative max-w-sm">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="text" wire:model.live="permissionSearch"
                           placeholder="Cari resource atau permission..."
                           class="perm-search-input block w-full rounded-lg bg-white dark:bg-gray-900 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 shadow-sm placeholder:text-gray-400 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 border-gray-300"
                           style="padding-left: 2.5rem; border-width: 1px; border-style: solid;"
                           autocomplete="off" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10">
                            <th class="w-12 px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase">No</th>
                            <th class="px-4 py-3 text-left text-sm font-medium text-gray-500 uppercase">Resource</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Create">C</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Read">R</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Update">U</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Delete">D</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Approve">A</th>
                            <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase" title="Reject">Rj</th>
                            <th class="w-28 px-4 py-3 text-center text-sm font-medium text-gray-500 uppercase">Pilih Semua</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $no = 0; @endphp
                        @foreach($this->groupedPermissions as $resource => $perms)
                            @php
                                $no++;
                                $permByAction = [];
                                foreach ($perms as $perm) {
                                    $action = explode('_', $perm->name)[0];
                                    $permByAction[$action] = $perm;
                                }
                            @endphp
                            <tr class="border-b border-gray-100 dark:border-white/5 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 text-gray-500">{{ $no }}</td>
                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300">{{ $resource }}</td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['create'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['create']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['view'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['view']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['update'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['update']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['delete'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['delete']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['approve'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['approve']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @isset($permByAction['reject'])
                                        <input type="checkbox" wire:model="selectedPermissions.{{ $permByAction['reject']->name }}"
                                               class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500" />
                                    @endisset
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <input type="checkbox"
                                           class="h-5 w-5 rounded border-gray-300 text-amber-600 shadow-sm focus:ring-amber-500"
                                           onclick="
                                               var c=this.checked;
                                               var r=this.closest('tr');
                                               r.querySelectorAll('input[type=checkbox]').forEach(function(cb){
                                                   if(cb!=this){cb.checked=c;cb.dispatchEvent(new Event('change',{bubbles:true}));}
                                               }.bind(this));
                                           " />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <x-filament::button type="submit" color="primary">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
