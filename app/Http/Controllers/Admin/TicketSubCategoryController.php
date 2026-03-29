<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Illuminate\Http\Request;

class TicketSubCategoryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = TicketSubCategory::with('category');

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where('name', 'like', "%{$search}%")
                      ->orWhereHas('category', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            }

            $total = $query->count();
            $limit = $request->length ?? 10;
            $start = $request->start ?? 0;
            
            $subCategories = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => TicketSubCategory::count(),
                'recordsFiltered' => $total,
                'data' => $subCategories
            ]);
        }

        return view('admin.tickets.subcategories.index');
    }

    public function create()
    {
        $categories = TicketCategory::where('status', 1)->get();
        return view('admin.tickets.subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
            'name' => 'required|string|max:255'
        ]);
        TicketSubCategory::create($request->all());
        return redirect()->route('admin.ticket-sub-categories.index')->with('success', 'Sub-category created successfully.');
    }

    public function edit(TicketSubCategory $ticketSubCategory)
    {
        $categories = TicketCategory::where('status', 1)->get();
        return view('admin.tickets.subcategories.edit', compact('ticketSubCategory', 'categories'));
    }

    public function update(Request $request, TicketSubCategory $ticketSubCategory)
    {
        $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
            'name' => 'required|string|max:255'
        ]);
        $ticketSubCategory->update($request->all());
        return redirect()->route('admin.ticket-sub-categories.index')->with('success', 'Sub-category updated successfully.');
    }

    public function destroy(TicketSubCategory $ticketSubCategory)
    {
        $ticketSubCategory->delete();
        return redirect()->route('admin.ticket-sub-categories.index')->with('success', 'Sub-category deleted successfully.');
    }

    public function toggleStatus(TicketSubCategory $ticketSubCategory)
    {
        $ticketSubCategory->update(['status' => !$ticketSubCategory->status]);
        return response()->json(['success' => true]);
    }
}
