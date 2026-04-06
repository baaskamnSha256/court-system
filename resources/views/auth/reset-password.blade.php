<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Шинэ нууц үг тохируулах</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 antialiased">

<div class="min-h-screen flex flex-col lg:flex-row">

    <div class="hidden lg:flex lg:w-3/5 xl:w-2/3 bg-slate-800 flex-col items-center justify-center p-12 text-white">
        <img src="/logo.png" class="w-48 h-48 object-contain mb-8 opacity-95" alt="Court Logo">
        <h1 class="text-xl xl:text-2xl font-bold text-center max-w-xl leading-snug">
            БАЯНЗҮРХ, СҮХБААТАР, ЧИНГЭЛТЭЙ ДҮҮРГИЙН ЭРҮҮГИЙН ХЭРГИЙН АНХАН ШАТНЫ ТОЙРГИЙН ШҮҮХ
        </h1>
        <p class="mt-4 text-slate-300 text-center max-w-md text-sm">
            Нууц үгээ шинэчилсний дараа нэвтрэх хуудас руу буцна уу.
        </p>
    </div>

    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8">

                <h2 class="text-xl font-semibold text-slate-800 text-center mb-2">
                    Шинэ нууц үг тохируулах
                </h2>
                <p class="text-sm text-slate-600 text-center mb-6">
                    Доор шинэ нууц үгээ оруулна уу.
                </p>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ request()->route('token') }}">

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Имэйл</label>
                        <input type="email" name="email" value="{{ old('email', request('email')) }}" required autofocus
                               autocomplete="email"
                               class="w-full rounded-xl border-slate-300 bg-slate-50/50 px-4 py-2.5 text-slate-800 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:bg-white transition-colors">
                    </div>

                    <x-auth.password-field
                        label="Шинэ нууц үг"
                        name="password"
                        id="reset-password"
                        autocomplete="new-password"
                        required
                    />

                    <x-auth.password-field
                        label="Нууц үг давтах"
                        name="password_confirmation"
                        id="reset-password-confirmation"
                        autocomplete="new-password"
                        required
                    />

                    <button type="submit"
                            class="w-full py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors">
                        Хадгалах
                    </button>
                </form>

                <div class="text-center mt-6">
                    <a href="{{ route('login') }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">
                        ← Нэвтрэх рүү буцах
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
