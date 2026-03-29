<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = Ticket::with(['user', 'category', 'subCategory']);

            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where('title', 'like', "%{$search}%")
                      ->orWhereHas('user', function($q) use ($search) {
                          $q->where('name', 'like', "%{$search}%");
                      });
            }

            $total = $query->count();
            $limit = $request->length ?? 10;
            $start = $request->start ?? 0;
            
            $tickets = $query->latest()->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => Ticket::count(),
                'recordsFiltered' => $total,
                'data' => $tickets
            ]);
        }

        return view('admin.tickets.index');
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'category', 'subCategory']);
        return view('admin.tickets.show', compact('ticket'));
    }

    public function resolve(Request $request, Ticket $ticket)
    {
        $request->validate([
            'admin_note' => 'required|string',
            'status' => 'required|in:resolved,closed'
        ]);

        $ticket->update([
            'admin_note' => $request->admin_note,
            'status' => $request->status,
            'resolved_at' => now()
        ]);

        return redirect()->route('admin.tickets.index')->with('success', 'Ticket status updated.');
    }
}
