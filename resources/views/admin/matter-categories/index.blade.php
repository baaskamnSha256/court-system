@extends('layouts.dashboard')

@section('title', 'Зүйл анги')
@section('header', 'Зүйл анги')

@section('content')
<div class="max-w-3xl space-y-6">
    @if (session('success'))
        <div class="p-4 rounded-lg bg-green-50 border border-green-200 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Тоо + Хайх + Шинэ нэмэх (нэг мөр, жижиг) --}}
    <div class="flex flex-col gap-4">
        <div class="text-gray-600">
            <span class="font-semibold text-gray-900">{{ $total }}</span> зүйл анги бүртгэгдсэн.
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <form method="GET" action="{{ route('admin.matter-categories.index') }}" class="flex flex-wrap items-center gap-2">
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Зүйл анги хайх..."
                       class="border border-gray-300 rounded-md px-3 py-2 w-48 sm:w-56 text-sm">
                <button type="submit" class="px-3 py-2 rounded-md bg-slate-700 hover:bg-slate-800 text-white text-sm">
                    Хайх
                </button>
                @if (request('q'))
                    <a href="{{ route('admin.matter-categories.index') }}" class="px-2 py-2 rounded-md border border-gray-300 hover:bg-gray-50 text-sm">
                        Цэвэрлэх
                    </a>
                @endif
            </form>
            <div class="border-l border-gray-200 pl-3 ml-1">
                <form method="POST" action="{{ route('admin.matter-categories.store') }}" class="flex items-center gap-2" onsubmit="return confirm('Шинэ зүйл анги нэмэх уу? Зөвшөөрч байна уу?');">
                    @csrf
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           maxlength="255"
                           class="border border-gray-300 rounded-md px-2.5 py-1.5 text-sm w-40 sm:w-48"
                           placeholder="Шинэ зүйл анги (нэр)">
                    <button type="submit" class="px-3 py-1.5 rounded-md bg-blue-700 hover:bg-blue-800 text-white text-sm whitespace-nowrap">
                        Нэмэх
                    </button>
                </form>
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Жагсаалт --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <h3 class="text-sm font-semibold text-gray-700 px-4 py-3 border-b border-gray-100">Бүртгэлтэй зүйл ангиуд</h3>
        @if ($matterCategories->isEmpty())
            <p class="p-6 text-gray-500 text-sm">Зүйл анги олдсонгүй.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($matterCategories as $cat)
                    <li class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                        <span class="font-medium text-gray-900">{{ $cat->name }}</span>
                        <form method="POST" action="{{ route('admin.matter-categories.destroy', $cat) }}" class="inline"
                              onsubmit="return confirm('Энэ зүйл ангийг устгах уу? Зөвшөөрч байна уу?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                Устгах
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <p class="text-sm text-gray-500">
        <a href="{{ route('admin.settings.index') }}" class="text-blue-600 hover:underline">← Тохиргоо руу буцах</a>
    </p>
</div>
@endsection
