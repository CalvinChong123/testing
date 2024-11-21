<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class HandleRequestLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = $request->header('accept-language');

        if ($locale) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
