<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOfficeController extends Controller
{
    public function index(): View
    {
        $offices = Office::query()
            ->with('representative:id,name')
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.offices.index', compact('offices', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:offices,name'],
            'representative_user_id' => ['required', 'exists:users,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        Office::query()->create($validated);

        return redirect()->route('admin.offices.index')->with('success', 'Office created.');
    }

    public function update(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('offices', 'name')->ignore($office->id)],
            'representative_user_id' => ['required', 'exists:users,id'],
            'contact_email' => ['nullable', 'email', 'max:255'],
        ]);

        $office->update($validated);

        return redirect()->route('admin.offices.index')->with('success', 'Office updated.');
    }

    public function updateStatus(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $office->update($validated);

        return redirect()->route('admin.offices.index')->with('success', 'Office status updated.');
    }
}
