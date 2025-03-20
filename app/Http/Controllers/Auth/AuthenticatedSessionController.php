<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $user = Auth::user();

        $request->session()->regenerate();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Error en la autenticación.');
        }

        if ($user->role_id == 1) {
            $staff = \DB::table('staff')->where('file_number', $user->file_number)->first();
            if ($staff) {
                return redirect()->route('staff.administration_panel', ['id' => $staff->id]);
            }
        } elseif ($user->role_id == 2) {
            return redirect()->route('staff.list');
        }

        return redirect()->intended(RouteServiceProvider::HOME);

    }



    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

