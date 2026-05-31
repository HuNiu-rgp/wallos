<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $intendedUrl = $request->session()->pull('url.intended', route('dashboard', absolute: false));
        $appUrl = (string) config('app.url');

        if (
            str_starts_with(strtolower($appUrl), 'https://')
            && str_starts_with(strtolower($intendedUrl), 'http://')
            && parse_url($appUrl, PHP_URL_HOST) === parse_url($intendedUrl, PHP_URL_HOST)
        ) {
            $intendedUrl = preg_replace('/^http:/i', 'https:', $intendedUrl);
        }

        return redirect()->to($intendedUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
