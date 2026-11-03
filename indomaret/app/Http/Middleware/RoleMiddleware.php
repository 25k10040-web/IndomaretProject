<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Silakan masuk terlebih dahulu.');
        }

        $user = auth()->user();

        if (!in_array($user->role, $roles)) {
            // Redirect user to their own module based on their role
            return match ($user->role) {
                'ukasir' => redirect()->route('pos.index')->with('warning', 'Akses ditolak: Anda diarahkan ke Modul Kasir.'),
                'ugudang' => redirect()->route('gudang.index')->with('warning', 'Akses ditolak: Anda diarahkan ke Modul Gudang.'),
                'uadmin' => redirect()->route('admin.dashboard')->with('warning', 'Akses ditolak: Anda diarahkan ke Modul Admin.'),
                default => abort(403, 'Akses tidak sah untuk peran Anda.'),
            };
        }

        return $next($request);
    }
}
