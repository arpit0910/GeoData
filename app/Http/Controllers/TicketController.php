<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        $categories = TicketCategory::where('status', 1)->with('subCategories')->get();
        $tickets = Ticket::where('user_id', auth()->id())
            ->with(['category', 'subCategory'])
            ->latest()
            ->get();

        return view('user.support.index', compact('categories', 'tickets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:ticket_categories,id',
            'sub_category_id' => 'nullable|exists:ticket_sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        $data = $request->only(['category_id', 'sub_category_id', 'title', 'description']);
        $data['user_id'] = auth()->id();
        $data['status'] = 'pending';

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('tickets', 'public');
            $data['file_path'] = $path;
        }

        Ticket::create($data);

        return redirect()->back()->with('success', 'Your ticket has been submitted successfully.');
    }

    public function getSubCategories(TicketCategory $category)
    {
        return response()->json($category->subCategories()->where('status', 1)->get(['id', 'name']));
    }
}
