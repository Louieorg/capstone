<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    private const VALID_ROLES = ['user', 'adviser', 'admin'];

    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount(['feedbacks', 'votes', 'comments', 'savedIdeas']);

        if ($request->filled('search')) {
            $search = (string) $request->search;
            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        $role = (string) $request->get('role', 'all');

        if (in_array($role, self::VALID_ROLES, true)) {
            $query->where('role', $role);
        }

        $sort = (string) $request->get('sort', 'newest');

        match ($sort) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        $users = $query->paginate(15)->withQueryString();

        $counts = [
            'all' => User::count(),
            'user' => User::where('role', 'user')->count(),
            'adviser' => User::where('role', 'adviser')->count(),
            'admin' => User::where('role', 'admin')->count(),
        ];

        return view('admin.users.index', compact('users', 'counts', 'role', 'sort'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|in:'.implode(',', self::VALID_ROLES),
        ]);

        if ($user->id === auth()->id()) {
            return back()->with('info', 'You cannot change your own role.');
        }

        $user->update(['role' => $request->string('role')->value()]);

        return back()->with('success', "Role updated for {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('info', 'You cannot delete your own account.');
        }

        $user->delete();

        return back()->with('success', 'User account removed.');
    }
}
