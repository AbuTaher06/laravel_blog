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
