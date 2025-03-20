<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión.');
        }

        $user = Auth::user();
        $roles = array_map('intval', $roles);

        if (in_array($user->role_id, $roles)) {
            return $next($request);
        }

        // Obtener el ID del staff si existe
        $staff = \DB::table('staff')->where('file_number', $user->file_number)->first();
        if ($staff) {
            session()->flash('staff_id', $staff->id);
        }

        return redirect()->route('unauthorized')->with('error', 'No tienes permiso para acceder a esta página.');
    }


}