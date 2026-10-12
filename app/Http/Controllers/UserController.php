<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q')),
            'role' => Role::tryFrom((string) $request->query('role'))?->value,
        ];

        $users = User::query()
            ->withCount('sales')
            ->when($filters['q'] !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$filters['q']}%")
                ->orWhere('email', 'like', "%{$filters['q']}%")))
            ->when($filters['role'], fn ($q, $role) => $q->where('role', $role))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::cases(),
        ]);
    }

    public function create(): View
    {
        return view('users.create', [
            'user' => new User(['role' => Role::Kasir, 'is_active' => true]),
            'roles' => Role::cases(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        // The `hashed` cast hashes the password.
        $user = User::create($request->validated());
        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
    }

    public function edit(User $user): View
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => Role::cases(),
        ]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // An admin may not lock themselves out.
        if ($request->user()->is($user)) {
            $errors = [];
            if ($data['role'] !== Role::Admin->value) {
                $errors['role'] = 'Anda tidak dapat menurunkan peran akun Anda sendiri.';
            }
            if (! $data['is_active']) {
                $errors['is_active'] = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
            }
            if ($errors) {
                throw ValidationException::withMessages($errors);
            }
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil diperbarui.");
    }

    /** Blocked when the user has sales (history must stay intact); deactivate instead. */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        if ($user->sales()->exists()) {
            return back()->with('error', 'Pengguna memiliki riwayat transaksi, nonaktifkan saja.');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Pengguna {$user->name} berhasil dihapus.");
    }

    public function toggleActive(User $user): RedirectResponse
    {
        Gate::authorize('toggleActive', $user);

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        return back()->with('success', $user->is_active
            ? "Akun {$user->name} diaktifkan kembali."
            : "Akun {$user->name} dinonaktifkan. Pengguna tidak bisa masuk lagi.");
    }
}
