<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use Illuminate\Http\Request;

class TicketCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = TicketCategory::query();

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where('name', 'like', "%{$search}%");
            }

            $total = $query->count();
            $limit = $request->length ?? 10;
            $start = $request->start ?? 0;
            
            $categories = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => TicketCategory::count(),
                'recordsFiltered' => $total,
                'data' => $categories
            ]);
        }

        return view('admin.tickets.categories.index');
    }

    public function create()
    {
        return view('admin.tickets.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        TicketCategory::create($request->all());
        return redirect()->route('admin.ticket-categories.index')->with('success', 'Category created successfully.');
    }

    public function edit(TicketCategory $ticketCategory)
    {
        return view('admin.tickets.categories.edit', compact('ticketCategory'));
    }

    public function update(Request $request, TicketCategory $ticketCategory)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $ticketCategory->update($request->all());
        return redirect()->route('admin.ticket-categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(TicketCategory $ticketCategory)
    {
        $ticketCategory->delete();
        return redirect()->route('admin.ticket-categories.index')->with('success', 'Category deleted successfully.');
    }

    public function toggleStatus(TicketCategory $ticketCategory)
    {
        $ticketCategory->update(['status' => !$ticketCategory->status]);
        return response()->json(['success' => true]);
    }
}
