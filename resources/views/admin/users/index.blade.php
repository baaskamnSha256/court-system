@extends('layouts.dashboard')

@section('title','Хэрэглэгч удирдах')
@section('header','Хэрэглэгч удирдах')

@section('content')
@php
    $roleLabels = [
        'admin' => 'Админ',
        'judge' => 'Шүүгч',
        'secretary' => 'Шүүгчийн туслах',
        'prosecutor' => 'Прокурор',
        'court_clerk' => 'Шүүх хуралын нарийн бичгийн дарга',
        'info_desk' => 'Мэдээлэл лавлагаа',
    ];
@endphp
<div x-data="{ openCreate:false, openEdit:false, editUser:{} }" class="space-y-4">

    {{-- Top bar: search + button --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
    <form
    x-data="{
        t: null,
        submit() { this.$el.submit(); },
        debounced() {
            clearTimeout(this.t);
            this.t = setTimeout(() => this.submit(), 400);
        }
    }"
    method="GET"
    action="{{ route('admin.users.index') }}"
    class="flex flex-col md:flex-row gap-2 md:items-center"
>

    <input
        type="text"
        name="q"
        value="{{ $q ?? request('q') }}"
        placeholder="Нэр / имэйл / утас / регистрээр хайх…"
        class="border rounded-md px-3 py-2 w-full md:w-72"
        @input="debounced()"
    >

    <select
        name="role"
        class="border rounded-md px-3 py-2 w-full md:w-56"
        @change="submit()"
    >
        <option value="all">Бүх эрх</option>
        @foreach($roles as $r)
            <option value="{{ $r->name }}" @selected(($role ?? request('role')) === $r->name)>
                {{ $roleLabels[$r->name] ?? $r->name }}
            </option>
        @endforeach
    </select>

    <select
        name="status"
        class="border rounded-md px-3 py-2 w-full md:w-44"
        @change="submit()"
    >
        <option value="all" @selected(($status ?? request('status','all')) === 'all')>Бүгд</option>
        <option value="active" @selected(($status ?? request('status')) === 'active')>Идэвхтэй</option>
        <option value="inactive" @selected(($status ?? request('status')) === 'inactive')>Идэвхгүй</option>
    </select>

    {{-- Хүсвэл “Хайх” товчийг үлдээж болно (гар ажиллагааны backup) --}}
    <button type="submit" class="px-4 py-2 rounded-md bg-blue-700 hover:bg-blue-800 text-white w-full md:w-auto">
        Хайх
    </button>

    @if(request('q') || request('role') || request('status'))
        <a href="{{ route('admin.users.index') }}"
           class="px-3 py-2 rounded-md border text-center w-full md:w-auto">
            Цэвэрлэх
        </a>
    @endif
</form>
        <button
            type="button"
            @click="openCreate=true"
            class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800">
        Хэрэглэгч нэмэх
        </button>
    </div>
    {{-- Role counts --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-8 gap-6">
    <div class="rounded-lg border bg-white p-3">
        <div class="text-xs text-gray-500">Нийт хэрэглэгч</div>
        <div class="text-2xl font-extrabold">{{ $totalUsers ?? 0 }}</div>
    </div>

    @php
        $cards = [
            'admin' => 'border-blue-200 bg-blue-50 text-blue-900',
            'judge' => 'border-green-200 bg-green-50 text-green-900',
            'secretary' => 'border-indigo-200 bg-indigo-50 text-indigo-900',
            'lawyer' => 'border-amber-200 bg-amber-50 text-amber-900',
            'prosecutor' => 'border-red-200 bg-red-50 text-red-900',
            'info_desk' => 'border-purple-200 bg-slate-50 text-slate-900',
            'court_clerk' => 'border--200 bg-slate-50 text-slate-900',
        ];
    @endphp

    @foreach($cards as $roleName => $cls)
        <div class="rounded-lg border p-3 {{ $cls }}">
            <div class="text-xs opacity-80">{{ $roleLabels[$roleName] ?? strtoupper($roleName) }}</div>
            <div class="text-2xl font-extrabold">
                {{ $roleCounts[$roleName] ?? 0 }} 
            </div>
        </div>
    @endforeach
</div>

    @if(session('success'))
        <div class="p-3 rounded-md bg-green-50 border border-green-200 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Users table --}}
    <div class="bg-white rounded-lg border overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
            <tr class="text-left">
                <th class="p-3">Нэр</th>
                <th class="p-3">И-мэйл</th>
                <th class="p-3">Утасны дугаар</th>
                <th class="p-3">РД</th>
                <th class="p-3">Ажил</th>
                <th class="p-3">Эрх</th>
                <th class="p-3">Идэвх</th>
                <th class="p-3 text-right">Үйлдэл</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
                <tr class="border-t">
                    <td class="p-3">{{ $u->name }}</td>
                    <td class="p-3">{{ $u->email }}</td>
                    <td class="p-3">{{ $u->phone ?? '—' }}</td>
                    <td class="p-3">{{ $u->register_number ?? '—' }}</td>
                    <td class="p-3">{{ $u->workplace ?? '—' }}</td>
                    <td class="p-3">{{ $roleLabels[$u->getRoleNames()->first()] ?? $u->getRoleNames()->first() ?? '—' }}</td>
                    <td class="p-3">
                        @if($u->is_active)
                            <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs font-semibold">Active</span>
                        @else
                            <span class="px-2 py-1 rounded bg-gray-100 text-gray-700 text-xs font-semibold">Inactive</span>
                        @endif
                    </td>
                    <td class="p-3 text-right">
                    <button
                        type="button"
                         class="px-3 py-1.5 rounded-md border hover:bg-blue-400"
                        @click="
                        editUser = {
                        id: {{ $u->id }},
                        name: @js($u->name),
                        email: @js($u->email),
                        phone: @js($u->phone),
                        register_number: @js($u->register_number),
                        workplace: @js($u->workplace),
                        role: @js($u->getRoleNames()->first()),
                        is_active: {{ $u->is_active ? 'true' : 'false' }},
                        };
                        openEdit = true;
                        "
                        >
                        Засварлах
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="p-3 text-gray-500" colspan="8">Хэрэглэгч олдсонгүй.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $users->links() }}
    </div>

    {{-- MODAL: Create User --}}
    <div
        x-show="openCreate"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center"
        aria-modal="true"
        role="dialog"
    >
        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="openCreate=false"></div>

        {{-- Modal panel --}}
        <div class="relative bg-white w-full max-w-xl rounded-xl shadow-xl border p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold">Шинэ хэрэглэгч нэмэх</h3>
                <button class="text-gray-600 hover:text-gray-900" @click="openCreate=false">✕</button>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-semibold mb-1">Нэр</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full border rounded-md px-3 py-2">
                    @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Имэйл</label>
                        <input type="email" name="email" value="{{ old('email') }}"
                               class="w-full border rounded-md px-3 py-2">
                        @error('email')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Утас</label>
                        <input name="phone" value="{{ old('phone') }}"
                               class="w-full border rounded-md px-3 py-2"
                               placeholder="8 оронтой (ж: 99112233)">
                        @error('phone')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Регистрийн дугаар</label>
                        <input name="register_number" value="{{ old('register_number') }}"
                               class="w-full border rounded-md px-3 py-2 uppercase"
                               oninput="this.value=this.value.toUpperCase()"
                               placeholder="АБ12345678">
                        @error('register_number')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-semibold mb-1">Нууц үг</label>
                        <input type="password" name="password"
                               class="w-full border rounded-md px-3 py-2">
                        @error('password')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ажилладаг газар</label>
                    <input name="workplace" value="{{ old('workplace') }}"
                           class="w-full border rounded-md px-3 py-2">
                    @error('workplace')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Эрх</label>
                        <select name="role" class="w-full border rounded-md px-3 py-2">
                            @foreach($roles as $role)
                                @php $rn = is_string($role) ? $role : $role->name; @endphp
                                <option value="{{ $rn }}">{{ $roleLabels[$rn] ?? $rn }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex items-center gap-2 pt-7">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300">
                        <span class="text-sm">Идэвхтэй</span>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCreate=false"
                            class="px-4 py-2 rounded-md border">
                        Болих
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800">
                        Хадгалах
                    </button>
                </div>
            </form>

            {{-- Хэрвээ validation error гарвал modal автоматаар нээгдүүлэх --}}
            @if($errors->any())
                <script>
                    document.addEventListener('alpine:init', () => {});
                </script>
            @endif

        </div>
    </div>
    {{-- MODAL: Edit User --}}
<div
    x-show="openEdit"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center"
    aria-modal="true"
    role="dialog"
>
    <div class="absolute inset-0 bg-black/50" @click="openEdit=false"></div>

    <div class="relative bg-white w-full max-w-xl rounded-xl shadow-xl border p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold">Хэрэглэгч засварлах</h3>
            <button class="text-gray-600 hover:text-gray-900" @click="openEdit=false">✕</button>
        </div>

        <form method="POST" :action="'{{ url('/admin/users') }}/' + editUser.id" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-semibold mb-1">Нэр</label>
                <input type="text" name="name" x-model="editUser.name"
                       class="w-full border rounded-md px-3 py-2">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Имэйл</label>
                    <input type="email" name="email" x-model="editUser.email"
                           class="w-full border rounded-md px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Утас</label>
                    <input name="phone" x-model="editUser.phone"
                           class="w-full border rounded-md px-3 py-2"
                           placeholder="99112233">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Регистрийн дугаар</label>
                    <input name="register_number" x-model="editUser.register_number"
                           class="w-full border rounded-md px-3 py-2 uppercase"
                           oninput="this.value=this.value.toUpperCase()"
                           placeholder="АБ12345678">
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1">Ажилладаг газар</label>
                    
                    <input name="workplace" x-model="editUser.workplace"
                           class="w-full border rounded-md px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Эрх</label>
                    <select name="role" class="w-full border rounded-md px-3 py-2" x-model="editUser.role">
                        @foreach($roles as $role)
                            @php $rn = is_string($role) ? $role : $role->name; @endphp
                            <option value="{{ $rn }}">{{ $roleLabels[$rn] ?? $rn }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2 pt-7">
                    <input type="checkbox" name="is_active" value="1"
                           class="rounded border-gray-300"
                           :checked="editUser.is_active"
                           @change="editUser.is_active = $event.target.checked">
                    <span class="text-sm">Идэвхтэй</span>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="openEdit=false" class="px-4 py-2 rounded-md border">
                    Болих
                </button>
                <button type="submit" class="px-4 py-2 rounded-md bg-blue-700 text-white hover:bg-blue-800">
                    Хадгалах
                </button>
            </div>
        </form>
    </div>
</div>

</div>

{{-- Validation error гарвал modal нээх (Alpine variable-г Blade-ээр эхлүүлэх) --}}
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Alpine хожим ачаалж магадгүй тул бага зэрэг delay
        setTimeout(() => {
            const root = document.querySelector('[x-data]');
            if (root && root.__x) root.__x.$data.openCreate = true;
        }, 50);
    });
</script>
@endif

@endsection