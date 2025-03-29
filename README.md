# Step 1: Setup Laravel Project

## 1️⃣ Install Laravel & Sanctum

Run the following commands:

```bash
composer create-project --prefer-dist laravel/laravel Laravelblog

cd Laravelblog

composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## 2️⃣ Configure Sanctum

In `app/Http/Kernel.php`, add middleware:

```php
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

protected $middlewareGroups = [
    'api' => [
        EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

In `config/cors.php`, update:

```php
'paths' => ['api/*', 'sanctum/csrf-cookie'],
```

---

# Step 2: Create Models, Migrations & Controllers

## 1️⃣ Generate Models and Migrations

Run the following commands:

```bash
php artisan make:model Post -mcr
php artisan make:model Category -mcr
php artisan make:model Comment -mcr
php artisan make:controller AuthController

```
*The `-mcr` option creates the model, migration, and controller.*

---

# Step 3: Define Database Schema

## 1️⃣ Post table `database/migrations/xxxx_xx_xx_create_posts_table.php`

```php
public function up()
{
    Schema::create('posts', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('content');
        $table->foreignId('category_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->string('image')->nullable();
        $table->timestamps();
    });
}

```

## 2️⃣ Categories Table `database/migrations/xxxx_xx_xx_create_categories_table.php`

```php
public function up()
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();
        $table->string('name')->unique();
        $table->timestamps();
    });
}

```
## 2️⃣ Comments Table `database/migrations/xxxx_xx_xx_create_comments_table.php`

```php
public function up()
public function up()
{
    Schema::create('comments', function (Blueprint $table) {
        $table->id();
        $table->text('body');
        $table->foreignId('post_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });
}


```

Run migrations:

```bash
php artisan migrate
```

---

# Step 4: Define Model Relationships

## 1️⃣ In `app/Models/Post.php`

```php
class Post extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'content', 'category_id', 'user_id', 'image'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

```

## 2️⃣ In `app/Models/Category.php`

```php
class Category extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

```
## 2️⃣ In `app/Models/Comment.php`

```php
class Comment extends Model
{
    use HasFactory;
    protected $fillable = ['body', 'post_id', 'user_id'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

---

# Step 5: Implement API Endpoints

## 1️⃣ Define Routes in `routes/api.php`

```php
use App\Http\Controllers\PostController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('posts', PostController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('comments', CommentController::class);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

```

---

# Step 6: Implement Controllers

## 1️⃣ Create `AuthController` for User Authentication

Run:

```bash
php artisan make:controller AuthController
```

### `AuthController.php`

```php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['token' => $user->createToken('auth_token')->plainTextToken]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials']);
        }

        return response()->json(['token' => $user->createToken('auth_token')->plainTextToken]);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}

```

---

# Step 7: Implement PostControllers

## 1️⃣ `PostController.php`

```php
namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        return Post::with('category', 'user', 'comments')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
            'category_id' => 'required|exists:categories,id',
        ]);

        return Post::create(array_merge(
            $request->all(),
            ['user_id' => auth()->id()]
        ));
    }

    public function show(Post $post)
    {
        return $post->load('category', 'user', 'comments');
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        $post->update($request->all());
        return $post;
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }
}

```

## 2️⃣ `CategoryController.php`

```php
namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Project::with('tasks')->get();
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string']);
        return Project::create($request->all());
    }

    public function show(Project $project)
    {
        return $project->load('tasks');
    }

    public function update(Request $request, Project $project)
    {
        $project->update($request->all());
        return $project;
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(null, 204);
    }
}
```

---

# Step 8: Testing the API

Use Postman or any API testing tool to test the endpoints:

- **Register:** `POST /api/register`
- **Login:** `POST /api/login`
- **Projects:** 
  - `GET /api/projects`
  - `POST /api/projects`
  - `GET /api/projects/{id}`
  - `PUT /api/projects/{id}`
  - `DELETE /api/projects/{id}`
- **Tasks:**
  - `GET /api/tasks`
  - `POST /api/tasks`
  - `GET /api/tasks/{id}`
  - `PUT /api/tasks/{id}`
  - `DELETE /api/tasks/{id}`



# 🚀 Laravel Blog System: Custom Login & Registration

Let's build a custom authentication system without using Laravel Breeze or Jetstream. This includes:

- ✅ User Registration
- ✅ User Login
- ✅ User Logout
- ✅ Password Hashing & Session Handling
- ✅ Protecting Routes with Custom Middleware

---

## 📌 1. Create Custom Authentication Middleware

Run the following command to generate middleware:

```bash
php artisan make:middleware AuthMiddleware
```

This creates a file: `app/Http/Middleware/AuthMiddleware.php`.

---

## 📌 2. Implement Logic in AuthMiddleware.php

Modify `app/Http/Middleware/AuthMiddleware.php`:

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to access this page.');
        }
        return $next($request);
    }
}
```

---

## 📌 3. Register Middleware in Kernel.php

Add the middleware to the global middleware list in `app/Http/Kernel.php` inside the `$routeMiddleware` array:

```php
protected $routeMiddleware = [
    // Default middleware
    'auth.middleware' => \App\Http\Middleware\AuthMiddleware::class,
];
```

---

## 📌 4. Set Up the User Model & Migration

Laravel includes a User model and migration by default. To ensure authentication works properly, update the migration file located at `database/migrations/xxxx_xx_xx_create_users_table.php`:

```php
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    });
}
```

Now, migrate the database:

```bash
php artisan migrate
```

---

## 📌 5. Create Authentication Routes (routes/web.php)

Add routes for register, login, logout, and protect other routes with middleware:

```php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Show Login & Register Forms
Route::get('/register', [AuthController::class, 'showRegister'])->name('register.form');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login.form');

// Handle Registration & Login
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// Protect routes using custom middleware
Route::middleware(['auth.middleware'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

---

## 📌 6. Create AuthController.php

Generate a new controller:

```bash
php artisan make:controller AuthController
```

Now, modify `app/Http/Controllers/AuthController.php`:

```php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Show Register Page
    public function showRegister()
    {
        return view('auth.register');
    }

    // Show Login Page
    public function showLogin()
    {
        return view('auth.login');
    }

    // Handle User Registration
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('login.form')->with('success', 'Registration successful! Please log in.');
    }

    // Handle User Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            return redirect()->route('dashboard')->with('success', 'Login successful!');
        }

        return back()->with('error', 'Invalid login credentials.');
    }

    // Handle Logout
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login.form')->with('success', 'Logged out successfully.');
    }
}
```

---

## 📌 7. Create Views (resources/views/auth/)

### 📝 7.1 Register Page (resources/views/auth/register.blade.php)

```blade
@extends('layouts.app')

@section('content')
<h2>Register</h2>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('register') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control">
    </div>
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
    </div>
    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
    </div>
    <div class="mb-3">
        <label>Confirm Password</label>
        <input type="password" name="password_confirmation" class="form-control">
    </div>
    <button class="btn btn-primary">Register</button>
</form>
<a href="{{ route('login.form') }}">Already have an account? Login</a>
@endsection
```

### 📝 7.2 Login Page (resources/views/auth/login.blade.php)

```blade
@extends('layouts.app')

@section('content')
<h2>Login</h2>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form action="{{ route('login') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control">
    </div>
    <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control">
    </div>
    <button class="btn btn-primary">Login</button>
</form>
<a href="{{ route('register.form') }}">Don't have an account? Register</a>
@endsection
```

---

## 📌 8. Modify Navigation Bar (resources/views/layouts/app.blade.php)

Update the navigation bar to show login/register/logout options dynamically:

```blade
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">Blog</a>
        <div>
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                <a href="{{ route('logout') }}" class="btn btn-danger">Logout</a>
            @else
                <a href="{{ route('login.form') }}" class="btn btn-light">Login</a>
                <a href="{{ route('register.form') }}" class="btn btn-light">Register</a>
            @endauth
        </div>
    </div>
</nav>
```

---

## 📌 9. Protect Routes Using Custom Middleware

Apply middleware to protected routes in `web.php`:

```php
Route::middleware(['auth.middleware'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
```

---


