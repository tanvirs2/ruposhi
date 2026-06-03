<?php

namespace App\Http\Controllers;

use App\Models\ExtraExpense;
use Illuminate\Http\Request;

class ExtraExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = ExtraExpense::when($request->search, fn($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->type,     fn($q) => $q->where('type',     $request->type))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->from,     fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,       fn($q) => $q->whereDate('expense_date', '<=', $request->to))
            ->latest('expense_date');

        $expenses   = $query->paginate(20)->withQueryString();
        $categories = ExtraExpense::distinct()->pluck('category')->filter();
        $totalExpense = (clone $query->getQuery())->where('type', 'expense')->sum('amount');
        $totalDeposit = (clone $query->getQuery())->where('type', 'deposit')->sum('amount');

        // Recalculate totals from filtered result
        $allFiltered  = ExtraExpense::when($request->search, fn($q) =>
                $q->where('title', 'like', "%{$request->search}%")
            )
            ->when($request->type,     fn($q) => $q->where('type',     $request->type))
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->from,     fn($q) => $q->whereDate('expense_date', '>=', $request->from))
            ->when($request->to,       fn($q) => $q->whereDate('expense_date', '<=', $request->to));

        $totalExpense = $allFiltered->clone()->where('type', 'expense')->sum('amount');
        $totalDeposit = $allFiltered->clone()->where('type', 'deposit')->sum('amount');

        return view('expenses.index', compact('expenses', 'categories', 'totalExpense', 'totalDeposit'));
    }

    public function create()
    {
        // Pre-select type from query string if given: ?type=deposit
        $defaultType = request('type', 'expense');
        return view('expenses.create', compact('defaultType'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:expense,deposit',
            'title'        => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        ExtraExpense::create(array_merge(
            $request->only('type', 'title', 'amount', 'category', 'expense_date', 'notes'),
            ['user_id' => auth()->id()]
        ));

        $msg = $request->type === 'deposit' ? 'জমা সফলভাবে রেকর্ড হয়েছে।' : 'খরচ সফলভাবে যোগ করা হয়েছে।';
        return redirect()->route('expenses.index')->with('success', $msg);
    }

    public function edit(ExtraExpense $expense)
    {
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, ExtraExpense $expense)
    {
        $request->validate([
            'type'         => 'required|in:expense,deposit',
            'title'        => 'required|string|max:255',
            'amount'       => 'required|numeric|min:0',
            'expense_date' => 'required|date',
        ]);

        $expense->update($request->only('type', 'title', 'amount', 'category', 'expense_date', 'notes'));

        $msg = $expense->type === 'deposit' ? 'জমা আপডেট হয়েছে।' : 'খরচ সফলভাবে আপডেট করা হয়েছে।';
        return redirect()->route('expenses.index')->with('success', $msg);
    }

    public function destroy(ExtraExpense $expense)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'শুধুমাত্র অ্যাডমিন মুছতে পারবেন।');
        }
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'রেকর্ড মুছে ফেলা হয়েছে।');
    }
}
