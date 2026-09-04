<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromAcceptLanguage
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->localeFromHeader($request->header('Accept-Language', '')));

        return $next($request);
    }

    private function localeFromHeader(string $header): string
    {
        foreach (explode(',', $header) as $part) {
            $code = strtolower(trim(explode(';', $part)[0]));
            $code = str_replace('_', '-', $code);

            if ($code === 'ar' || str_starts_with($code, 'ar-')) {
                return 'ar';
            }
        }

        return 'en';
    }
}
