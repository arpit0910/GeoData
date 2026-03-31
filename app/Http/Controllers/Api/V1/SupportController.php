<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends Controller
{
    /**
     * List all active ticket categories.
     */
    public function categories()
    {
        $categories = TicketCategory::where('status', 1)->get(['id', 'name']);
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    /**
     * List all active subcategories for a specific category.
     */
    public function subcategories(TicketCategory $category)
    {
        if (!$category->status) {
            return response()->json(['success' => false, 'message' => 'Category is inactive.'], 403);
        }

        $subcategories = $category->subCategories()->where('status', 1)->get(['id', 'name']);
        return response()->json([
            'success' => true,
            'data' => $subcategories
        ]);
    }

    /**
     * List current user's tickets.
     */
    public function tickets(Request $request)
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->with(['category:id,name', 'subCategory:id,name'])
            ->latest()
            ->paginate($request->query('limit', 20));

        return response()->json([
            'success' => true,
            'data' => $tickets->items(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ]
        ]);
    }

    /**
     * Store a new support ticket via API.
     */
    public function storeTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:ticket_categories,id',
            'sub_category_id' => 'nullable|exists:ticket_sub_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['category_id', 'sub_category_id', 'title', 'description']);
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('tickets', 'public');
            $data['file_path'] = $path;
        }

        $ticket = Ticket::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully.',
            'data' => [
                'ticket_id' => $ticket->id,
                'status' => $ticket->status
            ]
        ], 201);
    }
}
