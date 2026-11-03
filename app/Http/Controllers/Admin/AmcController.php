<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amc;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmcController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'favicon' => ['nullable', 'in:available,missing'],
        ]);
        $search = trim($filters['search'] ?? '');
        $amcs = Amc::query()->withCount('funds')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company_name', 'like', "%{$search}%")
                ->orWhere('amfi_id', $search)))
            ->when(($filters['favicon'] ?? '') === 'available', fn ($query) => $query->whereNotNull('logo_path')->where('logo_path', '<>', ''))
            ->when(($filters['favicon'] ?? '') === 'missing', fn ($query) => $query->where(fn ($query) => $query->whereNull('logo_path')->orWhere('logo_path', '')))
            ->orderBy('name')->paginate(25)->withQueryString();

        $total = Amc::count();
        $withFavicons = Amc::whereNotNull('logo_path')->where('logo_path', '<>', '')->count();
        return view('admin.amcs.index', compact('amcs', 'search', 'total', 'withFavicons'));
    }

    public function show(Amc $amc): View
    {
        $amc->loadCount('funds');
        return view('admin.amcs.show', compact('amc'));
    }
}
