<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bank;
use App\Models\BankBranch;
use App\Models\City;
use App\Models\State;

class BankBranchController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = BankBranch::with(['bank', 'city', 'state']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('ifsc', 'like', "%{$search}%")
                      ->orWhere('branch', 'like', "%{$search}%")
                      ->orWhere('micr', 'like', "%{$search}%")
                      ->orWhereHas('bank', function($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('city', function($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('state', function($sq) use ($search) {
                          $sq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $totalFiltered = $query->count();
            
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;
            
            $branches = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => BankBranch::count(),
                'recordsFiltered' => $totalFiltered,
                'data' => $branches
            ]);
        }

        $banks = Bank::orderBy('name')->get();
        return view('admin.bank-branches.index', compact('banks'));
    }

    public function create()
    {
        $banks = Bank::orderBy('name')->get();
        // Only get states of India
        $states = State::whereHas('Country', function($query) {
            $query->where('name', 'India');
        })->orderBy('name')->get();
        
        $cities = collect();
        if (old('state_id')) {
            $cities = City::where('state_id', old('state_id'))->orderBy('name')->get();
        }
        
        return view('admin.bank-branches.create', compact('banks', 'states', 'cities'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'ifsc' => 'required|string|unique:bank_branches,ifsc',
            'branch' => 'required|string',
            'micr' => 'nullable|string',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'address' => 'nullable|string',
            'contact' => 'nullable|string',
            'swift' => 'nullable|string',
            'imps' => 'nullable|boolean',
            'rtgs' => 'nullable|boolean',
            'neft' => 'nullable|boolean',
            'upi' => 'nullable|boolean',
        ]);

        BankBranch::create($request->all());

        return redirect()->route('bank-branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(BankBranch $bankBranch)
    {
        $banks = Bank::orderBy('name')->get();
        // Only get states of India
        $states = State::whereHas('Country', function($query) {
            $query->where('name', 'India');
        })->orderBy('name')->get();
        
        // Ensure cities are linked strictly to the branch state
        $cities = collect();
        if ($bankBranch->state_id) {
            $cities = City::where('state_id', $bankBranch->state_id)->orderBy('name')->get();
        }
        
        return view('admin.bank-branches.edit', compact('bankBranch', 'banks', 'states', 'cities'));
    }

    public function update(Request $request, BankBranch $bankBranch)
    {
        $request->validate([
            'bank_id' => 'required|exists:banks,id',
            'ifsc' => 'required|string|unique:bank_branches,ifsc,' . $bankBranch->id,
            'branch' => 'required|string',
            'micr' => 'nullable|string',
            'city_id' => 'required|exists:cities,id',
            'state_id' => 'required|exists:states,id',
            'address' => 'nullable|string',
            'contact' => 'nullable|string',
            'swift' => 'nullable|string',
            'imps' => 'nullable|boolean',
            'rtgs' => 'nullable|boolean',
            'neft' => 'nullable|boolean',
            'upi' => 'nullable|boolean',
        ]);

        $bankBranch->update($request->all());

        return redirect()->route('bank-branches.index')->with('success', 'Branch updated successfully.');
    }

    public function destroy(BankBranch $bankBranch)
    {
        $bankBranch->delete();
        return redirect()->route('bank-branches.index')->with('success', 'Branch deleted successfully.');
    }
}
