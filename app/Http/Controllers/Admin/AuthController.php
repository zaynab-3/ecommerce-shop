<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create()
    {
        if (auth()->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $r)
    {
        $data = $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:200']);
        if (! Auth::attempt([...$data, 'is_active' => true], false)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }$r->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
