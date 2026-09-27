@extends('layouts.public')
@section('title', 'Company Fundamentals - SetuGeo Markets')
@section('meta_description', 'Browse company profiles, financial statements, key ratios, shareholding and competitor datasets.')
@section('market_heading', 'Company fundamentals')
@section('market_subheading', 'Profiles, financial statements, ratios and ownership datasets—loaded one record at a time.')
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-8">
    <form method="GET" class="market-panel p-4 grid sm:grid-cols-4 gap-3 mb-6"><input class="market-input sm:col-span-2" type="search" name="search" value="{{ request('search') }}" placeholder="Company, symbol or ISIN" aria-label="Search fundamentals"><select class="market-input" name="dataset" aria-label="Dataset"><option value="">All datasets</option>@foreach($datasets as $dataset)<option value="{{ $dataset }}" @selected(request('dataset') === $dataset)>{{ ucwords(str_replace('_', ' ', $dataset)) }}</option>@endforeach</select><button class="market-button" type="submit"><i class="fas fa-filter mr-2"></i>Apply</button></form>
    <p class="text-sm market-muted mb-4"><strong class="text-white">{{ number_format($fundamentals->total()) }}</strong> datasets found</p>
    <div class="market-panel overflow-x-auto"><table class="market-table"><thead><tr><th>Company</th><th>Dataset</th><th>Statement</th><th>Period</th><th>Updated</th><th></th></tr></thead><tbody>
        @forelse($fundamentals as $row)<tr><td><div class="font-bold text-white">{{ $row->equity?->company_name ?: $row->isin }}</div><div class="text-xs market-muted mt-1">{{ $row->equity?->nse_symbol ?: $row->equity?->bse_symbol }} · {{ $row->isin }}</div></td><td><span class="market-chip">{{ ucwords(str_replace('_', ' ', $row->dataset)) }}</span></td><td class="text-sm">{{ ucwords(str_replace('_', ' ', $row->statement_type)) }}</td><td class="text-sm">{{ ucwords(str_replace('_', ' ', $row->time_period)) }}</td><td class="text-xs market-muted">{{ optional($row->synced_at)->format('d M Y') }}</td><td class="text-right"><a href="{{ route('market.fundamentals.show', $row) }}" class="text-sm font-bold text-amber-400 hover:text-amber-300">View data <i class="fas fa-chevron-right ml-1"></i></a></td></tr>
        @empty<tr><td colspan="6" class="text-center market-muted py-12">No fundamental datasets match these filters.</td></tr>@endforelse
    </tbody></table></div><div class="market-pagination mt-6">{{ $fundamentals->links() }}</div>
</div></div>
@endsection
