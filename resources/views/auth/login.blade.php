<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>БЗ, СБ, ЧДүүргийн Эрүүгийн хэргийн анхан шатны тойргын шүүх</title>
    @vite('resources/css/app.css')
</head>

<body class="min-h-screen bg-slate-50 antialiased">

<div class="min-h-screen flex flex-col lg:flex-row">

    {{-- LEFT BRANDING --}}
    <div class="hidden lg:flex lg:w-3/5 xl:w-2/3 bg-slate-800 flex-col items-center justify-center p-12 text-white">
        <img src="/logo.png" class="w-48 h-48 object-contain mb-8 opacity-95" alt="Court Logo">
        <h1 class="text-xl xl:text-2xl font-bold text-center max-w-xl leading-snug">
            БАЯНЗҮРХ, СҮХБААТАР, ЧИНГЭЛТЭЙ ДҮҮРГИЙН ЭРҮҮГИЙН ХЭРГИЙН АНХАН ШАТНЫ ТОЙРГИЙН ШҮҮХ
        </h1>
        <p class="mt-4 text-slate-300 text-center max-w-md">
            Шүүх Хуралдааны зар товлох дотоод удирдлагын систем
        </p>
        <p class="mt-12 text-slate-500 text-sm">Developed by БЗСБЧДЭХАНТШ</p>
    </div>

    {{-- RIGHT LOGIN --}}
    <div class="flex-1 flex items-center justify-center p-6 lg:p-12">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 p-8">

                <h2 class="text-xl font-semibold text-slate-800 text-center mb-6">
                    Системд нэвтрэх
                </h2>

                @if (session('csrf_error'))
                    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm px-4 py-3">
                        {{ session('csrf_error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Имэйл эсвэл утас</label>
                        <input type="text" name="email" required autofocus value="{{ old('email') }}"
                               placeholder="example@court.mn эсвэл 8 оронтой дугаар"
                               autocomplete="username"
                               class="w-full rounded-xl border-slate-300 bg-slate-50/50 px-4 py-2.5 text-slate-800 placeholder-slate-400 focus:border-slate-400 focus:ring-2 focus:ring-slate-200 focus:bg-white transition-colors">
                        @error('email')<p class="mt-1 text-red-600 text-sm">{{ $message }}</p>@enderror
                    </div>

                    <x-auth.password-field
                        label="Нууц үг"
                        name="password"
                        id="password"
                        autocomplete="current-password"
                        required
                        oninvalid="this.setCustomValidity('Нууц үгээ оруулна уу')"
                        oninput="this.setCustomValidity('')"
                    />

                    <label class="flex cursor-pointer flex-wrap items-center justify-between gap-3 select-none">
                        <span class="text-sm font-medium text-slate-600">Намайг сана</span>
                        <span class="relative inline-flex h-7 w-11 shrink-0 items-center">
                            <input type="checkbox" name="remember" value="1" class="peer sr-only" @checked(old('remember'))>
                            <span aria-hidden="true" class="block h-7 w-11 rounded-full bg-slate-200 transition-colors peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-slate-400 peer-focus-visible:ring-offset-2 peer-checked:bg-slate-800"></span>
                            <span aria-hidden="true" class="pointer-events-none absolute left-0.5 top-1/2 size-5 -translate-y-1/2 rounded-full bg-white shadow ring-1 ring-slate-900/10 transition-[left] duration-200 ease-out peer-checked:left-[1.375rem]"></span>
                        </span>
                    </label>

                    <div class="text-center -mt-1">
                        <a href="{{ route('password.request') }}"
                           class="inline-block text-sm font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                            Нууц үг мартсан уу? Сэргээх
                        </a>
                    </div>

                    <button type="submit" class="w-full py-3 rounded-xl bg-slate-800 text-white font-semibold hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors">
                        Нэвтрэх
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

</body>
</html>
