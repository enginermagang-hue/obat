@extends('panduan.layout')

@section('title', $judul ?? 'Panduan')
@section('sidebar')
    @include('panduan.components.sidebar-nav', ['sections' => $sidebar, 'activeSlug' => $slug])
@endsection

@section('content')
<div class="px-6 lg:px-10 py-8 max-w-4xl">
    <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6" aria-label="Breadcrumb">
        <a href="{{ route('panduan.index') }}" class="hover:text-primary-600 dark:hover:text-primary-400 transition-colors">Panduan</a>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
        </svg>
        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $judul ?? 'Panduan' }}</span>
    </nav>

    <article class="prose-doc">
        {!! $html !!}
    </article>
</div>
@endsection

@section('toc')
<aside class="panduan-toc">
    <div class="panduan-toc-inner px-6 py-8">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-200 mb-3 uppercase tracking-wider">Di Halaman Ini</h3>
        <nav id="toc-nav" class="space-y-1">
            <p class="text-gray-400 dark:text-gray-500 italic text-xs">Memuat daftar isi...</p>
        </nav>
    </div>
</aside>
@endsection
