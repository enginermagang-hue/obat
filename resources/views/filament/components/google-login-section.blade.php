@php
    $user = auth()->user();
    $googleLink = \DutchCodingCompany\FilamentSocialite\Models\SocialiteUser::query()
        ->where('user_id', $user->id)
        ->where('provider', 'google')
        ->first();
@endphp

<div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
    <div>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
            Akun Google
        </p>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            @if ($googleLink)
                Terhubung sebagai <span class="font-medium">{{ $googleLink->provider_id }}</span>
            @else
                Belum terhubung dengan Google
            @endif
        </p>
    </div>

    @if ($googleLink)
        <form action="{{ route('auth.google.unlink') }}" method="POST" class="inline">
            @csrf
            <x-filament::button type="submit" color="danger" outlined size="sm">
                Putuskan
            </x-filament::button>
        </form>
    @else
        <x-filament::button
            tag="a"
            href="{{ route('auth.google.link') }}"
            color="primary"
            outlined
            size="sm"
            x-data
            @click.prevent="window.location.href = '{{ route('auth.google.link') }}'"
        >
            Hubungkan
        </x-filament::button>
    @endif
</div>
