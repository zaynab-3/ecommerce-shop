<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAccess
{
    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login');
        }
        abort_unless(auth()->user()->is_active, 403);
        if ($permission === 'catalog') {
            abort_unless(auth()->user()->canManageCatalog(), 403);
        }
        if ($permission === 'owner') {
            abort_unless(auth()->user()->role === 'owner', 403);
        }

        return $next($request);
    }
}
