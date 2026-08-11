<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }} }"
        class="relative w-full"
    >
        <span class="ruang-obat-search-input-icon">
            <x-heroicon-m-magnifying-glass class="h-4 w-4" />
        </span>

        <input
            type="text"
            x-model="state"
            placeholder="{{ $getPlaceholder() }}"
            class="ruang-obat-search-input"
        >

        <button
            type="button"
            x-show="state"
            @click="state = ''"
            class="absolute inset-y-0 end-2 flex items-center text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
        >
            <x-heroicon-m-x-mark class="h-4 w-4" />
        </button>
    </div>
</x-dynamic-component>
