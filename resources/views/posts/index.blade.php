@extends('layouts.app')

@section('title', 'All Posts')

@section('content')
    <h2 class="mb-4">All Posts</h2>
    @foreach ($posts as $post)
        <div class="card mb-3">
            <div class="card-body">
                <h4><a href="{{ route('posts.show', $post->id) }}">{{ $post->title }}</a></h4>
                <p>{{ Str::limit($post->content, 100) }}</p>
                <small>By {{ $post->user->name }} on {{ $post->created_at->format('d M Y') }}</small>
            </div>
        </div>
    @endforeach
    {{ $posts->links() }}
@endsection
