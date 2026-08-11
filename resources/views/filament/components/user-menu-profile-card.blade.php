@php
    $user = filament()->auth()->user();
    $faskes = $user?->fasilitasKesehatan;
    $avatarUrl = $user?->getFilamentAvatarUrl();
    $profileUrl = $user ? \App\Filament\Pages\ProfilSaya::getUrl() : null;
@endphp

<a
    href="{{ $profileUrl }}"
    class="fi-user-menu-profile-card rounded-t-lg flex items-center gap-3 px-4 py-3 transition duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/50"
>
    <div class="flex-shrink-0">
        @if ($avatarUrl)
            <img
                src="{{ $avatarUrl }}"
                alt="{{ $user->name }}"
                class="h-10 w-10 rounded-full object-cover"
                loading="lazy"
            />
        @else
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-500 text-sm font-medium text-white"
            >
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ $user->name }}
        </p>
        <p class="truncate text-xs text-gray-500 dark:text-gray-400">
            {{ $user->email }}
        </p>
        @if ($faskes)
            <p class="truncate text-xs text-gray-400 dark:text-gray-500">
                @php
                    $tipeLabel = $faskes->tipe === 'puskesmas' ? 'Puskesmas' : 'Pustu';
                @endphp
                {{ $tipeLabel }} {{ $faskes->nama }}
            </p>
        @endif
    </div>
</a>

@if($user && ($user->hasRole('super_admin') || $user->hasRole('admin_dinas')))
    <div class="border-t border-gray-100 dark:border-gray-700">
        @livewire('rko-access-toggle')
    </div>
@endif
