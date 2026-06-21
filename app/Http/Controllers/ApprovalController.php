<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\PendingEdit;

class ApprovalController extends Controller
{
    public function index()
    {
        // Pending delete requests
        $pendingDeleteSales = Sale::whereNotNull('delete_requested_at')
            ->with('customer:id,name', 'deleteRequestedBy:id,name')
            ->latest('delete_requested_at')
            ->get();

        $pendingDeletePurchases = Purchase::whereNotNull('delete_requested_at')
            ->with('supplier:id,name', 'deleteRequestedBy:id,name')
            ->latest('delete_requested_at')
            ->get();


        // Pending edit requests
        $pendingEdits = PendingEdit::where('status', 'pending')
            ->with('requestedBy:id,name')
            ->latest()
            ->get();

        // History: recent resolved requests (last 30)
        $editHistory = PendingEdit::whereIn('status', ['approved', 'rejected', 'no_change', 'superseded'])
            ->with(['requestedBy:id,name', 'decidedBy:id,name'])
            ->latest()
            ->limit(30)
            ->get();

        return view('approvals.index', compact(
            'pendingDeleteSales', 'pendingDeletePurchases',
            'pendingEdits', 'editHistory'
        ));
    }
}
