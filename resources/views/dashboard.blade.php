@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Welcome, {{ auth()->user()->name }}!</h2>

    <div class="alert alert-success">
        You are logged in successfully!
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Dashboard Overview</h5>
            <p class="card-text">Here you can manage your blog posts.</p>

            <a href="{{ route('posts.create') }}" class="btn btn-primary">Create New Post</a>
            <a href="{{ route('home') }}" class="btn btn-secondary">View All Posts</a>
        </div>
    </div>

    <hr>

    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
        @csrf
        <button type="submit" class="btn btn-danger">Logout</button>
    </form>

</div>
@endsection
