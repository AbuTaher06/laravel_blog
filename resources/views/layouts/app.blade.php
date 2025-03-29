<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Blog System')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('dashboard') }}">Blog</a>
            <div>
                @auth
                    <a href="{{ route('posts.create') }}" class="btn btn-primary">New Post</a>
                    <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-light">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-light">Register</a>
                @endauth
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        @yield('content')
    </div>
</body>
</html>
