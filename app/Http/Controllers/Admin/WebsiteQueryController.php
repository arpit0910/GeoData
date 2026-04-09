<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteQueryController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = WebsiteQuery::latest()->get();
            return datatables()->of($data)
                ->addIndexColumn()
                ->editColumn('created_at', function($row){
                    return Auth::user()->formatDate($row->created_at);
                })
                ->addColumn('action', function($row){
                    $btn = '<button onclick="viewQuery('.$row->id.')" class="bg-amber-100 text-amber-600 p-2 rounded-lg hover:bg-amber-200 transition-colors mr-2"><i class="fas fa-eye"></i></button>';
                    $btn .= '<button onclick="deleteQuery('.$row->id.')" class="bg-red-100 text-red-600 p-2 rounded-lg hover:bg-red-200 transition-colors"><i class="fas fa-trash"></i></button>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.website_queries.index');
    }

    public function show($id)
    {
        $websiteQuery = WebsiteQuery::findOrFail($id);
        return response()->json(array_merge($websiteQuery->toArray(), [
            'formatted_date' => Auth::user()->formatDate($websiteQuery->created_at)
        ]));
    }

    public function destroy($id)
    {
        $websiteQuery = WebsiteQuery::findOrFail($id);
        $websiteQuery->delete();
        return response()->json(['success' => 'Query deleted successfully.']);
    }

    public function markAsViewed($id)
    {
        $websiteQuery = WebsiteQuery::findOrFail($id);
        $websiteQuery->update(['status' => 'viewed']);
        return response()->json(['success' => 'Query marked as viewed.']);
    }
}
