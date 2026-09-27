@extends('layouts.public')
@section('title', 'Company Fundamental Data - SetuGeo Markets')
@section('market_heading', $companyFundamental->equity?->company_name ?: $companyFundamental->isin)
@section('market_subheading', ucwords(str_replace('_', ' ', $companyFundamental->dataset)).' · '.$companyFundamental->isin)
@section('content')
<div class="market-shell">@include('website.partials.market-nav')
<div class="market-wrap py-8 max-w-5xl">
    <a href="{{ route('market.fundamentals', request()->only(['search','dataset'])) }}" class="inline-flex items-center text-sm font-bold text-amber-400 mb-5"><i class="fas fa-arrow-left mr-2"></i>Back to fundamentals</a>
    <div class="grid sm:grid-cols-4 gap-3 mb-5">@foreach([['Dataset',ucwords(str_replace('_',' ',$companyFundamental->dataset))],['Statement',ucwords(str_replace('_',' ',$companyFundamental->statement_type))],['Period',ucwords(str_replace('_',' ',$companyFundamental->time_period))],['Updated',optional($companyFundamental->synced_at)->format('d M Y, h:i A')]] as [$label,$value])<div class="market-panel p-4"><div class="text-[10px] uppercase tracking-wider market-muted">{{ $label }}</div><div class="text-sm font-bold text-white mt-1">{{ $value ?: '—' }}</div></div>@endforeach</div>
    <div class="market-panel overflow-hidden"><div class="px-5 py-4 border-b border-white/10"><h2 class="font-bold text-white">Dataset payload</h2><p class="text-xs market-muted mt-1">This single-record view keeps the main fundamentals list fast.</p></div><pre class="p-5 text-xs sm:text-sm text-emerald-300 overflow-auto leading-relaxed max-h-[70vh]">{{ json_encode($companyFundamental->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div>
</div></div>
@endsection
