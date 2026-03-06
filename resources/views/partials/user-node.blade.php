<li>
    {{ $user->name }}

    @if($user->children->count())
        <ul>
            @foreach($user->children as $child)
                @include('partials.user-node', ['user' => $child])
            @endforeach
        </ul>
    @endif
</li>