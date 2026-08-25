<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetPatientLocale
{
    public function handle(Request $request, Closure $next)
    {
        App::setLocale('ar');

        return $next($request);
    }
}
