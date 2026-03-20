<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md min-h-screen">
        <div class="p-4 font-bold text-xl border-b">Admin Panel</div>
        <ul class="p-4">
            <li class="py-2"><a href="{{ route('admin.dashboard') }}" class="hover:text-blue-500">Dashboard</a></li>
            <li class="py-2"><a href="{{ route('admin.users') }}" class="hover:text-blue-500">Users</a></li>
        </ul>
    </aside>

    <!-- Main content -->
    <main class="flex-1 p-6">
        @yield('content')
    </main>

</body>
</html>
