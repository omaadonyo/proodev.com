<x-mail.layout subject="New user registered, {{ $user->name }}" docLabel="ADMIN ALERT">
    <h1>New user registered</h1>
    <p class="lead">A new {{ $user->role->label() }} just joined ProoDev.</p>

    <div class="grid">
        <div class="col">
            <div class="label">Name</div>
            <div class="value"><strong>{{ $user->name }}</strong></div>
        </div>
        <div class="col">
            <div class="label">Email</div>
            <div class="value">{{ $user->email }}</div>
        </div>
        <div class="col">
            <div class="label">Username</div>
            <div class="value">{{ $user->handle() }}</div>
        </div>
    </div>

    <div class="grid">
        <div class="col">
            <div class="label">Role</div>
            <div class="value">{{ $user->role->label() }}</div>
        </div>
        <div class="col">
            <div class="label">Registered</div>
            <div class="value">{{ $user->created_at->diffForHumans() }}</div>
        </div>
        <div class="col">
            <div class="label">Location</div>
            <div class="value">{{ $user->location ?: '-' }}</div>
        </div>
    </div>

    @if ($user->ownedCompany())
        <div class="divider"></div>
        <div class="value"><strong>Company account:</strong> {{ $user->ownedCompany()->name }}</div>
    @endif

    <div class="btn-row">
        <a class="btn" href="{{ route('admin.users') }}">View user</a>
    </div>
</x-mail.layout>
