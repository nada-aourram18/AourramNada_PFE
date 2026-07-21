<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            if (Auth::attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();

                return redirect()->intended(route('dashboard'));
            }
        } catch (ConnectionException $e) {
            Log::warning('Login Airtable connection failed', ['message' => $e->getMessage()]);

            return back()->withErrors([
                'email' => 'Impossible de joindre Airtable (réseau / SSL). Réessayez ou vérifiez AIRTABLE_HTTP_VERIFY.',
            ])->onlyInput('email');
        } catch (RequestException $e) {
            $status = $e->response?->status();
            Log::warning('Login Airtable request failed', [
                'status' => $status,
                'body' => $e->response?->body(),
            ]);

            if (in_array($status, [401, 403], true)) {
                return back()->withErrors([
                    'email' => 'Jeton Airtable invalide ou sans droits. Mettez à jour AIRTABLE_API_KEY (Personal Access Token) dans .env, puis php artisan config:clear.',
                ])->onlyInput('email');
            }

            return back()->withErrors([
                'email' => 'Erreur Airtable (HTTP '.$status.'). Vérifiez la base et les noms de tables.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
