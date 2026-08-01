<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    private const SESSION_KEY = 'public_locale';

    private const SUPPORTED_LOCALES = [
        'es',
        'en',
        'pt_BR',
        'fr',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        App::setLocale($locale);
        $request->setLocale($locale);

        return $next($request);
    }

    public static function supportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    private function resolveLocale(Request $request): string
    {
        $requestedLocale = $request->query('lang');

        if (is_string($requestedLocale) && $requestedLocale !== '') {
            $normalizedLocale = $this->normalizeLocale($requestedLocale);

            if ($normalizedLocale !== null) {
                $request->session()->put(self::SESSION_KEY, $normalizedLocale);

                return $normalizedLocale;
            }
        }

        $sessionLocale = $request->session()->get(self::SESSION_KEY);
        if (is_string($sessionLocale) && in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
            return $sessionLocale;
        }

        foreach ($request->getLanguages() as $browserLocale) {
            $normalizedLocale = $this->normalizeLocale($browserLocale);

            if ($normalizedLocale !== null) {
                $request->session()->put(self::SESSION_KEY, $normalizedLocale);

                return $normalizedLocale;
            }
        }

        $fallbackLocale = config('app.fallback_locale', 'en');

        return in_array($fallbackLocale, self::SUPPORTED_LOCALES, true) ? $fallbackLocale : 'en';
    }

    private function normalizeLocale(string $locale): ?string
    {
        $normalized = str_replace('-', '_', strtolower(trim($locale)));

        return match (true) {
            str_starts_with($normalized, 'es') => 'es',
            str_starts_with($normalized, 'en') => 'en',
            str_starts_with($normalized, 'pt') => 'pt_BR',
            str_starts_with($normalized, 'fr') => 'fr',
            default => null,
        };
    }
}
