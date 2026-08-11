@php $userCount = $record->users()->count(); @endphp

<div class="space-y-3">
    @if ($userCount > 0)
        <div class="flex items-start gap-3 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-400">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.515 2.625H3.72c-1.347 0-2.188-1.458-1.515-2.625L8.485 2.495zM10 6a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 6zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
            </svg>
            <div>
                <span class="font-semibold">Role ini masih digunakan.</span>
                <p class="mt-1">Terdapat <strong>{{ $userCount }}</strong> pengguna yang memiliki role <strong>{{ $record->name }}</strong>. Hapus atau ubah role pengguna tersebut terlebih dahulu.</p>
            </div>
        </div>
    @else
        <div class="flex items-start gap-3 rounded-lg bg-blue-50 p-4 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
            </svg>
            <div>
                <span class="font-semibold">Tidak ada pengguna yang menggunakan role ini.</span>
                <p class="mt-1">Role <strong>{{ $record->name }}</strong> aman untuk dihapus.</p>
            </div>
        </div>
    @endif
</div>
