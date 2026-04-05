<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrencyConversion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class CurrencyConversionController extends Controller
{
    public function index()
    {
        $rates = CurrencyConversion::with('country')->latest()->paginate(20);
        return view('admin.currency-conversions.index', compact('rates'));
    }

    public function sync()
    {
        try {
            Artisan::call('currency:fetch-rates');
            return redirect()->back()->with('success', 'Currency rates synced successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to sync currency rates: ' . $e->getMessage());
        }
    }
}
