@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>MLM Tree</h3>

        <form method="GET" action="{{ route('tree.index') }}" class="d-flex">
            <select name="user_id" class="form-select me-2" onchange="this.form.submit()">
                @foreach ($allUsers as $user)
                    <option value="{{ $user->id }}" {{ $rootUser->id === $user->id ? 'selected' : '' }}>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
            <noscript><button class="btn btn-primary">View</button></noscript>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <strong>Root:</strong> {{ $rootUser->name }}

            <ul class="mt-3">
                @include('partials.user-node', ['user' => $rootUser])
            </ul>
        </div>
    </div>
@endsection
