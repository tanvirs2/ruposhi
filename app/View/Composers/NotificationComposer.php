<?php

namespace App\View\Composers;

use App\Models\Sale;
use App\Models\Purchase;
use App\Models\PendingEdit;
use App\Models\Stock;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationComposer
{
    public function compose(View $view): void
    {
        // স্টক শেষ (quantity <= 0)
        $notifOutOfStock = Stock::where('quantity', '<=', 0)->count();

        // কম স্টক (quantity > 0 but <= min_quantity)
        $notifLowStock = Stock::where('quantity', '>', 0)
            ->where('min_quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'min_quantity')
            ->count();

        // কাস্টমার বাকী
        $notifCustomersDue     = Customer::where('due_amount', '>', 0)->count();
        $notifTotalCustomerDue = Customer::where('due_amount', '>', 0)->sum('due_amount');

        // সরবরাহকারী বকেয়া
        $notifSuppliersDue     = Supplier::where('due_amount', '>', 0)->count();
        $notifTotalSupplierDue = Supplier::where('due_amount', '>', 0)->sum('due_amount');

        // Pending approvals (admin only)
        $notifPendingApprovals = 0;
        if (Auth::check() && Auth::user()->canManageShop()) {
            $notifPendingApprovals = Sale::whereNotNull('delete_requested_at')->count()
                + Purchase::whereNotNull('delete_requested_at')->count()
                + PendingEdit::where('status', 'pending')->count();
        }

        $notifTotal = ($notifOutOfStock > 0 ? 1 : 0)
                    + ($notifLowStock   > 0 ? 1 : 0)
                    + ($notifCustomersDue  > 0 ? 1 : 0)
                    + ($notifSuppliersDue  > 0 ? 1 : 0)
                    + ($notifPendingApprovals > 0 ? 1 : 0);

        // Chat unread
        $chatUnread = Auth::check() ? ChatMessage::unreadCount(Auth::id()) : 0;

        $view->with([
            'notifOutOfStock'        => $notifOutOfStock,
            'notifLowStock'          => $notifLowStock,
            'notifCustomersDue'      => $notifCustomersDue,
            'notifTotalCustomerDue'  => $notifTotalCustomerDue,
            'notifSuppliersDue'      => $notifSuppliersDue,
            'notifTotalSupplierDue'  => $notifTotalSupplierDue,
            'notifPendingApprovals'  => $notifPendingApprovals,
            'notifTotal'             => $notifTotal,
            'chatUnread'             => $chatUnread,
        ]);
    }
}
