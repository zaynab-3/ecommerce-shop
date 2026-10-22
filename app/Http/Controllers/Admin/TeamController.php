<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class TeamController extends Controller
{
    public function index()
    {
        return view('admin.team', ['admins' => User::orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:120', 'email' => 'required|email|max:190|unique:users', 'password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()], 'role' => 'required|in:owner,manager,orders']);
        $user = User::create($data);
        AuditLog::record('admin.created', (string) $user->id);

        return back()->with('success', 'Admin account created.');
    }

    public function update(Request $r, User $user)
    {
        $data = $r->validate(['name' => 'sometimes|required|string|max:120', 'email' => ['sometimes', 'required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)], 'role' => 'required|in:owner,manager,orders', 'is_active' => 'required|boolean', 'password' => ['nullable', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]]);
        if (empty($data['password'])) {
            unset($data['password']);
        }DB::transaction(function () use ($user, $data) {
            $owners = User::where('role', 'owner')->where('is_active', true)->lockForUpdate()->get();
            if ($user->id === auth()->id() && (! $data['is_active'] || $data['role'] !== 'owner')) {
                abort(422, 'You cannot remove your own owner access.');
            }if ($user->role === 'owner' && $user->is_active && (! $data['is_active'] || $data['role'] !== 'owner') && $owners->count() <= 1) {
                abort(422, 'Keep at least one active owner.');
            }$user->update($data);
            AuditLog::record('admin.updated', (string) $user->id);
        });

        return back()->with('success', 'Admin account updated.');
    }
}
