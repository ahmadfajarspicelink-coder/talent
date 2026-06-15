<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users (R8.3).
     */
    public function index(): View
    {
        return view('users.index', [
            'users' => User::all(),
        ]);
    }

    /**
     * Store a newly created user with the given role (R8.1).
     *
     * The password is hashed automatically via the User model's
     * `password => hashed` cast.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil dibuat');
    }

    /**
     * Update only the role of the given user (R8.4).
     */
    public function update(UpdateUserRoleRequest $request, User $user): RedirectResponse
    {
        $user->update(['role' => $request->validated()['role']]);

        return redirect()
            ->route('users.index')
            ->with('success', 'Role pengguna berhasil diperbarui');
    }

    /**
     * Remove the given user, rejecting self-deletion (R8.5).
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'akun sendiri tidak bisa dihapus');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Pengguna berhasil dihapus');
    }
}
