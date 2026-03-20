<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Court System</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <nav>
        <!-- Навигацийн хэсэг -->
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
    </nav>
    <main>
        @yield('content')
    </main>
</body>
</html>