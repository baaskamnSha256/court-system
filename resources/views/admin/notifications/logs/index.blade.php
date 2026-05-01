@extends('layouts.dashboard')

@section('title', 'Мэдэгдлийн лог')

@section('content')
    <div class="space-y-4">
        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 lg:grid-cols-5">
            <input
                type="text"
                name="hearing_id"
                value="{{ request('hearing_id') }}"
                placeholder="Хурал ID"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >

            <input
                type="text"
                name="regnum"
                value="{{ request('regnum') }}"
                placeholder="Регистр"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >

            <input
                type="text"
                name="request_id"
                value="{{ request('request_id') }}"
                placeholder="Request ID"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"
            >

            <select name="delivery_status" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                <option value="">Төлөв (Бүгд)</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('delivery_status') === $status)>{{ $status }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">
                    Хайх
                </button>
                <a href="{{ route('admin.notifications.logs.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                    Цэвэрлэх
                </a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="px-3 py-2 text-left">Огноо</th>
                        <th class="px-3 py-2 text-left">Хурал</th>
                        <th class="px-3 py-2 text-left">Оролцогч</th>
                        <th class="px-3 py-2 text-left">Регистр</th>
                        <th class="px-3 py-2 text-left">Төлөв</th>
                        <th class="px-3 py-2 text-left">API</th>
                        <th class="px-3 py-2 text-left">Request ID</th>
                        <th class="px-3 py-2 text-left">Гарчиг</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-600">{{ optional($log->sent_at)->format('Y-m-d H:i:s') ?? $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-700">{{ $log->hearing_id ?? '—' }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-slate-800">{{ $log->recipient_role ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $log->recipient_name ?? '—' }}</div>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-700">{{ $log->regnum ?? '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">
                                @php
                                    $statusClass = match ($log->delivery_status) {
                                        'delivered' => 'bg-emerald-100 text-emerald-700',
                                        'not_registered' => 'bg-rose-100 text-rose-700',
                                        'failed', 'request_error', 'token_error', 'job_failed' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $statusClass }}">
                                    {{ $log->delivery_status }}
                                </span>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-slate-700">
                                @php
                                    $apiClass = ($log->api_status === 200)
                                        ? 'text-emerald-700'
                                        : (($log->api_status === 701) ? 'text-rose-700 font-semibold' : 'text-slate-700');
                                @endphp
                                <span class="{{ $apiClass }}">{{ $log->api_status ?? '—' }}</span>
                                @if($log->api_message)
                                    <div class="text-xs text-slate-500">{{ $log->api_message }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-slate-600">
                                @if($log->request_id)
                                    <div class="flex items-center gap-2">
                                        <span class="break-all">{{ $log->request_id }}</span>
                                        <button
                                            type="button"
                                            class="rounded border border-slate-300 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-100"
                                            onclick="navigator.clipboard.writeText('{{ $log->request_id }}')"
                                        >
                                            Хуулах
                                        </button>
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-3 py-2 text-slate-700">{{ $log->title }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">Мэдэгдлийн лог олдсонгүй.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $logs->links() }}
        </div>
    </div>
@endsection
