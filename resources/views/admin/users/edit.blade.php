@extends('layouts.dashboard')
@section('header','Хэрэглэгч засах')

@section('content')
<div class="max-w-2xl">
    <h1 class="text-xl font-bold mb-4">Хэрэглэгчийн мэдээлэл засах</h1>

    @if ($errors->any())
        <div class="mb-4 p-3 rounded bg-red-50 border border-red-200 text-red-700 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="text-sm font-semibold text-gray-700">Нэр</label>
            <input name="name" value="{{ old('name',$user->name) }}" required
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Имэйл</label>
            <input name="email" type="email" value="{{ old('email',$user->email) }}" required
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
        </div>
        <div>
    <label class="block text-sm font-semibold mb-1">Утасны дугаар</label>
    <input name="phone"
           value="{{ old('phone',$user->phone) }}"
           class="w-full border rounded-md px-3 py-2">
</div>

<div>
    <label class="block text-sm font-semibold mb-1">Регистрийн дугаар</label>
    <input name="register_number"
           value="{{ old('register_number',$user->register_number) }}"
           class="w-full border rounded-md px-3 py-2 uppercase">
</div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Хаана ажилладаг</label>
            <input name="workplace" value="{{ old('workplace',$user->workplace) }}"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                   placeholder="Алба/Хэлтэс/Байгууллага">
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Role</label>
            <select name="role" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2">
                @foreach($roles as $r)
                    <option value="{{ $r->name }}"
                        @selected(old('role', $user->getRoleNames()->first()) === $r->name)>
                        {{ $r->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="text-sm font-semibold text-gray-700">Нууц үг (заавал биш)</label>
            <input name="password" type="text"
                   class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2"
                   placeholder="Хоосон орхивол өөрчлөгдөхгүй">
        </div>

        <div class="flex gap-3 pt-2">
            <button class="px-4 py-2 rounded-md bg-blue-900 text-white hover:bg-blue-800">
                Хадгалах
            </button>
            <a href="{{ route('admin.users.index') }}"
               class="px-4 py-2 rounded-md bg-gray-100 border border-gray-200 hover:bg-gray-200">
                Буцах
            </a>
        </div>
    </form>
</div>
@endsection
