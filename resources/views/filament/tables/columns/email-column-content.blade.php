<div class="flex items-center gap-1 leading-none">
    <a href="mailto:{{ $email }}"
        class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 underline underline-offset-2">
        {{ $email }}
    </a>
    <button type="button"
            onclick="
                var btn = this;
                navigator.clipboard.writeText('{{ $email }}').then(function() {
                    btn.querySelector('.copy-icon').classList.add('hidden');
                    btn.querySelector('.check-icon').classList.remove('hidden');
                    setTimeout(function() {
                        btn.querySelector('.copy-icon').classList.remove('hidden');
                        btn.querySelector('.check-icon').classList.add('hidden');
                    }, 1500);
                });
            "
            title="Copy email"
        class="inline-flex shrink-0 p-0.5 text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-300 leading-none">
        <svg class="copy-icon size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M15.988 3.012A2.25 2.25 0 0118 5.25v6.5A2.25 2.25 0 0115.75 14H13.5V7A2.5 2.5 0 0011 4.5H5.324A2.25 2.25 0 017.5 3h6.5a2.25 2.25 0 011.988.012zM7 6a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2V8a2 2 0 00-2-2H7z" clip-rule="evenodd" />
        </svg>
        <svg class="check-icon hidden size-4 text-success-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
        </svg>
    </button>
</div>
