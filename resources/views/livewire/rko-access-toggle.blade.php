<div
    wire:click="toggle"
    role="button"
    tabindex="0"
    @keydown.enter="toggle"
    class="flex items-center justify-between gap-3 px-4 py-3 transition duration-150 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer select-none"
>
    <div class="flex items-center gap-3 min-w-0">
        <x-heroicon-m-clipboard-document-check class="h-5 w-5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
        <span class="text-sm text-gray-700 dark:text-gray-300">Pengisian RKO</span>
    </div>
    <div
        x-data="{ on: @js($enabled) }"
        x-on:click.stop="on = !on; $wire.toggle()"
        :class="on ? 'bg-teal-600' : 'bg-gray-200 dark:bg-gray-600'"
        class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
    >
        <span
            x-ref="toggle"
            x-bind:style="on ? 'transform: translateX(16px)' : 'transform: translateX(0)'"
            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
        />
    </div>
</div>
