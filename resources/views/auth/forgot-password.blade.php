<!DOCTYPE html>
<html lang="mn">
<head>
    <meta charset="UTF-8">
    <title>Нууц үг сэргээх</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

        <h1 class="text-xl font-bold text-center mb-4">
            🔑 Нууц үг сэргээх
        </h1>

        <p class="text-sm text-gray-600 text-center mb-6">
            Имэйл хаягаа оруулна уу. Нууц үг сэргээх холбоос илгээнэ.
        </p>

        @if (session('status'))
            <div class="mb-4 bg-green-100 text-green-700 p-3 rounded text-sm text-center">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-100 text-red-700 p-3 rounded text-sm text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf

            <div>
                <label class="text-sm font-medium text-gray-700">Имэйл</label>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    class="mt-1 w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg font-semibold"
            >
                📧 Илгээх
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:underline">
                ← Нэвтрэх рүү буцах
            </a>
        </div>
    </div>

</body>
</html>
