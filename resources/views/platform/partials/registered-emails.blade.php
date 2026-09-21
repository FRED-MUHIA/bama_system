@forelse($tenant->users->pluck('email')->filter()->unique() as $email)
    <a class="d-block" href="mailto:{{ $email }}">{{ $email }}</a>
@empty
    <span class="text-muted">No registered email</span>
@endforelse
