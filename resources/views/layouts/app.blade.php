<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Court System') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-blue-600 text-white p-4 flex justify-between items-center">
        <h1 class="text-lg font-bold">{{ config('app.name', 'Court System') }}</h1>
        <nav class="space-x-4">
            @auth
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="inline">

                
                    @csrf
                    <button type="submit" class="underline">Logout</button>
                </form>
            @endauth
        </nav>
    </header>

    <div class="flex">
        <aside class="w-64 bg-white p-4 shadow min-h-screen">
            <ul class="space-y-2">
                @hasanyrole('admin|head_of_department')
                    <li><a href="{{ route('admin.dashboard') }}">Admin Dashboard</a></li>
                @endhasanyrole

                @role('judge')
                    <li><a href="{{ route('judge.dashboard') }}">Judge Dashboard</a></li>
                @endrole

                @role('secretary')
                    <li><a href="{{ route('secretary.dashboard') }}">Шүүгчийн туслах самбар</a></li>
                @endrole
            </ul>
        </aside>

        <main class="flex-1 p-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
