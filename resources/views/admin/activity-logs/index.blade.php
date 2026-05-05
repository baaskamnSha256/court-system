@extends('layouts.dashboard')

@section('title', 'Үйлдлийн түүх')

@section('content')
    <div class="space-y-4">
        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-4">
            <input
                type="text"
                name="user"
                value="{{ request('user') }}"
                placeholder="Хэрэглэгч (нэр эсвэл имэйл)"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >

            <input
                type="text"
                name="case_no"
                value="{{ request('case_no') }}"
                placeholder="Хэргийн дугаар"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
            >

            <select name="category" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100">
                <option value="">Төрөл (бүгд)</option>
                <option value="auth" @selected(request('category') === 'auth')>Нэвтрэлт / гарах</option>
                <option value="http" @selected(request('category') === 'http')>HTTP (POST/PUT/устгах)</option>
                <option value="file" @selected(request('category') === 'file')>Файл таталт</option>
                <option value="hearing" @selected(request('category') === 'hearing')>Хурлын зар</option>
            </select>

            <div class="flex flex-wrap items-center gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-200 dark:text-slate-900 dark:hover:bg-white">
                    Хайх
                </button>
                <a href="{{ route('admin.activity-logs.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                    Цэвэрлэх
                </a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <tr>
                        <th class="px-3 py-2 text-left">Огноо</th>
                        <th class="px-3 py-2 text-left">Хэрэглэгч</th>
                        <th class="px-3 py-2 text-left">Эрх (түүхэнд)</th>
                        <th class="px-3 py-2 text-left">Үйлдэл</th>
                        <th class="px-3 py-2 text-left">Тайлбар</th>
                        <th class="px-3 py-2 text-left">Хэрэг №</th>
                        <th class="px-3 py-2 text-left">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                    @forelse($logs as $log)
                        @php
                            $propRoles = data_get($log->properties, 'roles');
                            $rolesText = is_array($propRoles) ? implode(', ', $propRoles) : null;
                        @endphp
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-600 dark:text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-3 py-2">
                                @if($log->user)
                                    <div class="font-medium text-slate-800 dark:text-slate-100">{{ $log->user->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 max-w-[12rem] truncate text-xs text-slate-600 dark:text-slate-400" title="{{ $rolesText ?? '' }}">{{ $rolesText ?: '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-xs text-slate-700 dark:text-slate-300">{{ $log->action }}</td>
                            <td class="px-3 py-2 text-slate-800 dark:text-slate-200">{{ $log->description }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-700 dark:text-slate-300">{{ data_get($log->properties, 'case_no') ?? '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-500 dark:text-slate-400">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-slate-500 dark:text-slate-400">Бичлэг олдсонгүй.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="text-center text-xs text-slate-500 dark:text-slate-400">
            Нэг хуудсанд хамгийн ихдээ {{ $perPage }} бичлэг харагдана.
        </p>

        @if($logs->hasPages())
            <div class="flex justify-center">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
@endsection
