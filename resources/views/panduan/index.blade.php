@extends('panduan.layout')

@section('title', 'Panduan Aplikasi')
@section('sidebar')
    @include('panduan.components.sidebar-nav', ['sections' => $sidebar, 'activeSlug' => null])
@endsection

@section('content')
<div class="px-6 lg:px-10 py-8 max-w-4xl">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6" aria-label="Breadcrumb">
        <a href="{{ route('panduan.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Panduan</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-gray-700 dark:text-gray-300 font-medium">Overview</span>
    </nav>

    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Panduan Aplikasi RUANG OBAT</h1>
    <p class="text-lg text-gray-600 dark:text-gray-400 mb-10 max-w-2xl">Pilih topik di bawah untuk mempelajari cara menggunakan Sistem Informasi Manajemen Obat.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($panduan as $item)
            <a href="{{ route('panduan.show', $item['slug']) }}"
               class="group block bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:border-primary-300 dark:hover:border-primary-500 hover:shadow-md p-5 transition-all duration-200">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors mb-1">
                            {{ $item['judul'] }}
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed line-clamp-2">
                            {{ $item['deskripsi'] }}
                        </p>
                    </div>
                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 group-hover:text-primary-500 dark:group-hover:text-primary-400 group-hover:translate-x-1 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection
