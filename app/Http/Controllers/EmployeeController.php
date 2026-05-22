<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::when($request->search, fn($q) =>
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('phone', 'like', "%{$request->search}%")
        )->latest()->paginate(15);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
        ]);

        Employee::create($request->only('name', 'phone', 'email', 'position', 'salary', 'join_date', 'address', 'status'));

        return redirect()->route('employees.index')->with('success', 'কর্মচারী সফলভাবে যোগ করা হয়েছে।');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'salary' => 'nullable|numeric|min:0',
        ]);

        $employee->update($request->only('name', 'phone', 'email', 'position', 'salary', 'join_date', 'address', 'status'));

        return redirect()->route('employees.index')->with('success', 'কর্মচারী সফলভাবে আপডেট করা হয়েছে।');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'কর্মচারী মুছে ফেলা হয়েছে।');
    }
}
