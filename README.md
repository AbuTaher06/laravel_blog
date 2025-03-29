# Step 1: Setup Laravel Project

## 1️⃣ Install Laravel & Sanctum

Run the following commands:

```bash
composer create-project --prefer-dist laravel/laravel TaskManager
cd TaskManager
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



# Step 8: Using web routes


