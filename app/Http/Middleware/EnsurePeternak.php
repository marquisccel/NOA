<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman kontrak, kandang, dan siklus hanya untuk peternak (F8.2 / R13): ABK tidak boleh masuk.
 */
class EnsurePeternak
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->isPeternak(), 403);

        return $next($request);
    }
}
