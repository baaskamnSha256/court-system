@extends('layouts.dashboard')
@section('header', $headerTitle ?? 'Текст нууцлах')

@section('content')
<div class="space-y-6">
    @if(session('error'))
        <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-lg font-semibold text-slate-800">Урьдчилж харах</div>
                <div class="text-xs text-slate-500">Энэ нь DOCX-оос гаргасан best-effort текст урьдчилгаа (format бүрэн адил харагдахгүй).</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ $backUrl }}" class="inline-flex items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Буцах
                </a>
                <a href="{{ $downloadUrl }}" class="inline-flex items-center rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    DOCX татах
                </a>
            </div>
        </div>

        <textarea readonly rows="18"
                  class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono whitespace-pre-wrap focus:border-slate-500 focus:ring-1 focus:ring-slate-500">{{ $previewText }}</textarea>
    </div>
</div>
@endsection

