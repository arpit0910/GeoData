@extends('layouts.public')
@section('title', 'Corporate Actions - SetuGeo Markets')
@section('meta_description', 'Track company dividends, splits, bonuses, rights issues and other corporate actions.')
@section('market_heading', 'Corporate actions')
@section('market_subheading', 'Track dividends, splits, bonuses, rights issues and important company events.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-8">
    <form method="GET" class="market-panel p-4 grid sm:grid-cols-4 gap-3 mb-6"><input class="market-input sm:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Company, symbol, event or ISIN" aria-label="Search corporate actions"><select class="market-input" name="type" aria-label="Action type"><option value="">All action types</option>@foreach(['DIVIDEND','SPLIT','BONUS','RIGHTS','EVENT'] as $type)<option value="{{ $type }}" @selected(request('type') === $type)>{{ ucfirst(strtolower($type)) }}</option>@endforeach</select><button class="market-button" type="submit"><i class="fas fa-filter mr-2"></i>Apply</button></form>
    <p class="text-sm market-muted mb-4"><strong class="text-white">{{ number_format($actions->total()) }}</strong> actions found</p>
    <div class="market-panel overflow-x-auto"><table class="market-table"><thead><tr><th>Company</th><th>Action</th><th>Details</th><th>Ex-date</th><th>Record date</th></tr></thead><tbody>
        @forelse($actions as $action)<tr><td><div class="font-bold text-white">{{ $action->symbol ?: $action->company_name }}</div><div class="text-xs market-muted mt-1 max-w-xs truncate">{{ $action->company_name ?: $action->isin }}</div></td><td><span class="market-chip">{{ $action->type }}</span><div class="text-sm text-white mt-2">{{ $action->name }}</div></td><td class="text-sm market-muted max-w-md">{{ \Illuminate\Support\Str::limit($action->details ?: ($action->ratio ? 'Ratio '.$action->ratio : ($action->amount ? '₹'.number_format($action->amount, 2) : '—')), 130) }}</td><td class="text-sm font-semibold text-white">{{ optional($action->expiry_date)->format('d M Y') ?: '—' }}</td><td class="text-sm market-muted">{{ optional($action->record_date)->format('d M Y') ?: '—' }}</td></tr>
        @empty<tr><td colspan="5" class="text-center market-muted py-12">No corporate actions match these filters.</td></tr>@endforelse
    </tbody></table></div><div class="market-pagination mt-6">{{ $actions->links() }}</div>
</div></div>
@endsection
