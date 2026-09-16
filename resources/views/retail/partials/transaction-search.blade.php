<form method="GET" action="{{ route('retail.transactions.index') }}" class="d-flex flex-column flex-sm-row gap-2 mb-3" role="search">
    <input class="form-control" type="search" name="q" value="{{ request('q') }}" maxlength="255" placeholder="Transaction, customer, phone or payment reference" aria-label="Search transactions">
    <button class="btn btn-outline-dark" type="submit">Search</button>
</form>
