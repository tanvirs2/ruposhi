<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $rows = Category::withCount('items')->latest()->get();
        return view('item-meta.categories.index', compact('rows'));
    }

    public function create()
    {
        return view('item-meta.categories.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:categories,name']);
        Category::create($request->only('name', 'description'));
        return redirect()->route('categories.index')->with('success', 'ক্যাটাগরি যোগ করা হয়েছে।');
    }

    public function edit(Category $category)
    {
        return view('item-meta.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $request->validate(['name' => 'required|string|max:255|unique:categories,name,' . $category->id]);
        $category->update($request->only('name', 'description'));
        return redirect()->route('categories.index')->with('success', 'ক্যাটাগরি আপডেট হয়েছে।');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'ক্যাটাগরি মুছে ফেলা হয়েছে।');
    }
}
