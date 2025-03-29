@extends('layouts.app')

@section('title', 'Create Post')

@section('content')
    <h2>Create New Post</h2>
    <form action="{{ route('posts.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label>Title</label>
            <input type="text" name="title" class="form-control">
        </div>
        <div class="mb-3">
            <label>Content</label>
            <textarea name="content" class="form-control"></textarea>
        </div>
        <button class="btn btn-success">Create</button>
    </form>
@endsection
