<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelSetting extends Model
{
    public const SUPPORTED_CURRENCIES = [
        'BOB' => 'Bolivianos',
        'USD' => 'Dolares estadounidenses',
    ];

    protected $fillable = [
        'hotel_name',
        'legal_name',
        'slogan',
        'description_short',
        'description_long',
        'logo',
        'favicon',
        'cover_image',
        'theme_primary_color',
        'theme_secondary_color',
        'theme_accent_color',
        'theme_background_color',
        'theme_surface_color',
        'theme_text_color',
        'theme_muted_color',
        'hero_video',
        'hero_video_url',
        'mobile_hero_image',
        'address',
        'city',
        'country',
        'phone',
        'whatsapp',
        'contact_people',
        'email',
        'contact_emails',
        'website',
        'facebook',
        'instagram',
        'tiktok',
        'social_links',
        'google_maps_url',
        'check_in_time',
        'check_out_time',
        'currency',
        'enabled_currencies',
        'usd_exchange_rate',
        'tax_name',
        'tax_number',
        'payment_qr_image',
        'digital_wallet_qr_image',
        'bank_qr_image',
        'bank_name',
        'bank_account_holder',
        'bank_account_number',
        'payment_instructions',
        'reservation_expiration_minutes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'reservation_expiration_minutes' => 'integer',
            'usd_exchange_rate' => 'decimal:4',
            'social_links' => 'array',
            'enabled_currencies' => 'array',
            'contact_people' => 'array',
            'contact_emails' => 'array',
        ];
    }

    public static function current(): self
    {
        return self::query()->first() ?? new self([
            'hotel_name' => 'Hostal Cerro Rico',
            'city' => 'Potosi',
            'country' => 'Bolivia',
            'currency' => 'BOB',
            'enabled_currencies' => self::defaultCurrencies(),
            'usd_exchange_rate' => 6.96,
            'theme_primary_color' => '#2c1458',
            'theme_secondary_color' => '#c6811e',
            'theme_accent_color' => '#d66a55',
            'theme_background_color' => '#f4f0e8',
            'theme_surface_color' => '#fcfaf7',
            'theme_text_color' => '#14293f',
            'theme_muted_color' => '#667789',
            'is_active' => true,
        ]);
    }

    public function themePrimaryColor(): string
    {
        return $this->normalizeHexColor($this->theme_primary_color, '#2c1458');
    }

    public function themeSecondaryColor(): string
    {
        return $this->normalizeHexColor($this->theme_secondary_color, '#c6811e');
    }

    public function themeAccentColor(): string
    {
        return $this->normalizeHexColor($this->theme_accent_color, '#d66a55');
    }

    public function themeBackgroundColor(): string
    {
        return $this->normalizeHexColor($this->theme_background_color, '#f4f0e8');
    }

    public function themeSurfaceColor(): string
    {
        return $this->normalizeHexColor($this->theme_surface_color, '#fcfaf7');
    }

    public function themeTextColor(): string
    {
        return $this->normalizeHexColor($this->theme_text_color, '#14293f');
    }

    public function themeMutedColor(): string
    {
        return $this->normalizeHexColor($this->theme_muted_color, '#667789');
    }

    public function publicThemeCssVariables(): array
    {
        $primary = $this->themePrimaryColor();
        $secondary = $this->themeSecondaryColor();
        $accent = $this->themeAccentColor();
        $background = $this->themeBackgroundColor();
        $surface = $this->themeSurfaceColor();
        $text = $this->themeTextColor();
        $muted = $this->themeMutedColor();
        $primaryDark = $this->adjustHexBrightness($primary, -34);
        $primaryStrong = $this->adjustHexBrightness($primary, 22);
        $secondaryDark = $this->adjustHexBrightness($secondary, -22);
        $accentDark = $this->adjustHexBrightness($accent, -24);

        return [
            '--public-bg' => $background,
            '--public-surface' => $this->hexToRgbaString($surface, 0.92),
            '--public-card' => $surface,
            '--public-text' => $text,
            '--public-muted' => $muted,
            '--public-bg-rgb' => $this->hexToRgbString($background),
            '--public-card-rgb' => $this->hexToRgbString($surface),
            '--public-text-rgb' => $this->hexToRgbString($text),
            '--public-muted-rgb' => $this->hexToRgbString($muted),
            '--public-primary' => $primary,
            '--public-primary-dark' => $primaryDark,
            '--public-primary-strong' => $primaryStrong,
            '--public-primary-rgb' => $this->hexToRgbString($primary),
            '--public-primary-dark-rgb' => $this->hexToRgbString($primaryDark),
            '--public-secondary' => $secondary,
            '--public-secondary-dark' => $secondaryDark,
            '--public-secondary-rgb' => $this->hexToRgbString($secondary),
            '--public-accent' => $accent,
            '--public-accent-dark' => $accentDark,
            '--public-accent-rgb' => $this->hexToRgbString($accent),
            '--public-card-alt' => $this->adjustHexBrightness($secondary, 98),
            '--public-highlight' => $this->adjustHexBrightness($primary, 120),
        ];
    }

    public function youtubeHeroVideoId(): ?string
    {
        $url = trim((string) ($this->hero_video_url ?? ''));

        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');

        if (str_contains($host, 'youtu.be') && $path !== '') {
            return strtok($path, '/');
        }

        if (str_contains($host, 'youtube.com')) {
            parse_str((string) ($parts['query'] ?? ''), $query);

            if (! empty($query['v']) && is_string($query['v'])) {
                return $query['v'];
            }

            if (str_starts_with($path, 'embed/')) {
                return strtok(substr($path, 6), '/');
            }

            if (str_starts_with($path, 'shorts/')) {
                return strtok(substr($path, 7), '/');
            }
        }

        return null;
    }

    public function youtubeHeroEmbedUrl(): ?string
    {
        $videoId = $this->youtubeHeroVideoId();

        if (! $videoId) {
            return null;
        }

        return 'https://www.youtube.com/embed/'.$videoId.'?autoplay=1&mute=1&controls=0&showinfo=0&rel=0&loop=1&playlist='.$videoId.'&playsinline=1&modestbranding=1';
    }

    public function googleMapsPublicUrl(): ?string
    {
        $url = trim((string) ($this->google_maps_url ?? ''));
        $query = $this->googleMapsSearchQuery();

        if ($url !== '') {
            $parts = parse_url($url);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $path = trim((string) ($parts['path'] ?? ''), '/');

            if ((str_contains($host, 'google.') || str_contains($host, 'googleusercontent.')) && $path === '') {
                return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null;
            }

            return $url;
        }

        return $query !== '' ? 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query) : null;
    }

    public function googleMapsEmbedUrl(): ?string
    {
        $url = trim((string) ($this->google_maps_url ?? ''));
        $query = $this->googleMapsSearchQuery();

        if ($url === '') {
            return $query !== '' ? 'https://www.google.com/maps?q='.rawurlencode($query).'&output=embed' : null;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $query !== '' ? 'https://www.google.com/maps?q='.rawurlencode($query).'&output=embed' : null;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '');

        if ((str_contains($host, 'google.') || str_contains($host, 'googleusercontent.')) && str_contains($path, '/maps/embed')) {
            return $url;
        }

        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $url, $matches) === 1) {
            return 'https://www.google.com/maps?q='.$matches[1].','.$matches[2].'&output=embed';
        }

        if (str_contains($path, '/maps/place/')) {
            $place = strtok((string) str($path)->after('/maps/place/'), '/');
            $place = trim(urldecode(str_replace('+', ' ', (string) $place)));

            if ($place !== '') {
                return 'https://www.google.com/maps?q='.rawurlencode($place).'&output=embed';
            }
        }

        parse_str((string) ($parts['query'] ?? ''), $params);

        foreach (['q', 'query', 'll'] as $key) {
            if (! empty($params[$key]) && is_string($params[$key])) {
                return 'https://www.google.com/maps?q='.rawurlencode($params[$key]).'&output=embed';
            }
        }

        return $query !== '' ? 'https://www.google.com/maps?q='.rawurlencode($query).'&output=embed' : null;
    }

    public function publicSocialLinks(): array
    {
        $links = collect($this->social_links ?? [])
            ->filter(fn (mixed $link): bool => is_array($link) && filled($link['url'] ?? null))
            ->map(function (array $link): array {
                $label = trim((string) ($link['label'] ?? 'Red social'));
                $url = trim((string) ($link['url'] ?? ''));
                $icon = $this->normalizeSocialIcon($link['icon'] ?? null, $label, $url);

                return [
                    'label' => $label !== '' ? $label : 'Red social',
                    'url' => $url,
                    'icon' => $icon,
                ];
            });

        if ($links->isNotEmpty()) {
            return $links->values()->all();
        }

        return collect([
            ['label' => 'Facebook', 'url' => $this->facebook, 'icon' => 'bi-facebook'],
            ['label' => 'Instagram', 'url' => $this->instagram, 'icon' => 'bi-instagram'],
            ['label' => 'TikTok', 'url' => $this->tiktok, 'icon' => 'bi-tiktok'],
        ])->filter(fn (array $link): bool => filled($link['url']))->values()->all();
    }

    public function publicContactPeople(): array
    {
        $people = collect($this->contact_people ?? [])
            ->filter(fn (mixed $person): bool => is_array($person))
            ->map(function (array $person): ?array {
                $name = trim((string) ($person['name'] ?? ''));
                $role = trim((string) ($person['role'] ?? ''));
                $countryCode = preg_replace('/\D+/', '', (string) ($person['country_code'] ?? ''));
                $phone = preg_replace('/\D+/', '', (string) ($person['phone'] ?? ''));
                $photo = trim((string) ($person['photo'] ?? ''));

                if ($phone === '') {
                    return null;
                }

                if ($countryCode === '') {
                    $countryCode = '591';
                }

                $fullPhone = $countryCode.$phone;
                $message = rawurlencode('Hola, quiero consultar disponibilidad en '.($this->hotel_name ?: 'Hostal Cerro Rico').'.');

                return [
                    'name' => $name !== '' ? $name : 'Recepcion',
                    'role' => $role !== '' ? $role : 'Atencion al cliente',
                    'country_code' => $countryCode,
                    'phone' => $phone,
                    'display_phone' => '+'.$countryCode.' '.$phone,
                    'photo' => $photo,
                    'photo_url' => $photo !== '' ? asset('storage/'.$photo) : null,
                    'whatsapp_url' => 'https://wa.me/'.$fullPhone.'?text='.$message,
                ];
            })
            ->filter()
            ->values();

        if ($people->isNotEmpty()) {
            return $people->all();
        }

        $legacyPhone = preg_replace('/\D+/', '', (string) ($this->whatsapp ?? ''));

        if ($legacyPhone === '') {
            return [];
        }

        $countryCode = str_starts_with($legacyPhone, '591') ? '591' : '591';
        $phone = str_starts_with($legacyPhone, '591') ? substr($legacyPhone, 3) : $legacyPhone;
        $message = rawurlencode('Hola, quiero consultar disponibilidad en '.($this->hotel_name ?: 'Hostal Cerro Rico').'.');

        return [[
            'name' => 'Recepcion',
            'role' => 'Atencion al cliente',
            'country_code' => $countryCode,
            'phone' => $phone,
            'display_phone' => '+'.$countryCode.' '.$phone,
            'photo' => '',
            'photo_url' => null,
            'whatsapp_url' => 'https://wa.me/'.$countryCode.$phone.'?text='.$message,
        ]];
    }

    public function publicContactEmails(): array
    {
        $emails = collect($this->contact_emails ?? [])
            ->filter(fn (mixed $contact): bool => is_array($contact) && filled($contact['email'] ?? null))
            ->map(function (array $contact): array {
                $label = trim((string) ($contact['label'] ?? ''));
                $email = trim((string) ($contact['email'] ?? ''));

                return [
                    'label' => $label !== '' ? $label : 'Contacto',
                    'email' => $email,
                    'mailto_url' => 'mailto:'.$email,
                ];
            })
            ->values();

        if ($emails->isNotEmpty()) {
            return $emails->all();
        }

        return filled($this->email) ? [[
            'label' => 'Correo principal',
            'email' => $this->email,
            'mailto_url' => 'mailto:'.$this->email,
        ]] : [];
    }

    private function googleMapsSearchQuery(): string
    {
        return trim(collect([$this->hotel_name, $this->address, $this->city, $this->country])
            ->filter(fn (mixed $value): bool => filled($value))
            ->implode(', '));
    }

    public function baseCurrency(): string
    {
        $currency = strtoupper(trim((string) ($this->currency ?: 'BOB')));

        return array_key_exists($currency, $this->supportedCurrencies()) ? $currency : 'BOB';
    }

    public static function defaultCurrencies(): array
    {
        return [
            ['code' => 'BOB', 'name' => 'Bolivianos', 'symbol' => 'Bs.', 'is_base' => true],
            ['code' => 'USD', 'name' => 'Dolares estadounidenses', 'symbol' => '$us', 'is_base' => false],
        ];
    }

    public function currencyDefinitions(): array
    {
        $rows = collect($this->enabled_currencies ?? [])
            ->filter(fn (mixed $currency): bool => is_array($currency))
            ->map(function (array $currency): array {
                $code = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($currency['code'] ?? '')));
                $name = trim((string) ($currency['name'] ?? ''));
                $symbol = trim((string) ($currency['symbol'] ?? ''));

                return [
                    'code' => substr($code, 0, 10),
                    'name' => $name !== '' ? $name : $code,
                    'symbol' => $symbol !== '' ? $symbol : $code,
                    'is_base' => (bool) ($currency['is_base'] ?? false),
                ];
            })
            ->filter(fn (array $currency): bool => $currency['code'] !== '')
            ->unique('code')
            ->values();

        if ($rows->isEmpty()) {
            $rows = collect(self::defaultCurrencies());
        }

        $base = strtoupper(trim((string) ($this->currency ?: 'BOB')));

        if (! $rows->contains(fn (array $currency): bool => $currency['code'] === $base)) {
            $rows->prepend([
                'code' => $base,
                'name' => self::SUPPORTED_CURRENCIES[$base] ?? $base,
                'symbol' => $base === 'USD' ? '$us' : ($base === 'BOB' ? 'Bs.' : $base),
                'is_base' => true,
            ]);
        }

        return $rows
            ->map(fn (array $currency): array => [
                ...$currency,
                'is_base' => $currency['code'] === $base,
            ])
            ->values()
            ->all();
    }

    public function supportedCurrencies(): array
    {
        return collect($this->currencyDefinitions())
            ->mapWithKeys(fn (array $currency): array => [$currency['code'] => $currency['name']])
            ->all();
    }

    public function currencySymbols(): array
    {
        return collect($this->currencyDefinitions())
            ->mapWithKeys(fn (array $currency): array => [$currency['code'] => $currency['symbol']])
            ->all();
    }

    public function usdExchangeRate(): float
    {
        return max((float) ($this->usd_exchange_rate ?? 6.96), 0.0001);
    }

    public function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return array_key_exists($currency, $this->supportedCurrencies()) ? $currency : $this->baseCurrency();
    }

    public function resolveExchangeRate(?string $currency, float|int|string|null $requestedRate = null): float
    {
        $currency = $this->normalizeCurrency($currency);
        $baseCurrency = $this->baseCurrency();

        if ($currency === $baseCurrency) {
            return 1.0;
        }

        $rate = max((float) ($requestedRate ?? $this->usdExchangeRate()), 0.0001);

        return round($rate, 4);
    }

    public function affectsBaseLedger(?string $currency): bool
    {
        return $this->normalizeCurrency($currency) === $this->baseCurrency();
    }

    public function amountForBaseLedger(float $amount, ?string $currency): float
    {
        if (! $this->affectsBaseLedger($currency)) {
            return 0.0;
        }

        return round($amount, 2);
    }

    public function convertToBase(float $amount, ?string $currency, float|int|string|null $exchangeRate = null): float
    {
        $currency = $this->normalizeCurrency($currency);
        $baseCurrency = $this->baseCurrency();
        $rate = $this->resolveExchangeRate($currency, $exchangeRate);

        if ($currency === $baseCurrency) {
            return round($amount, 2);
        }

        if ($baseCurrency === 'BOB' && $currency === 'USD') {
            return round($amount * $rate, 2);
        }

        if ($baseCurrency === 'USD' && $currency === 'BOB') {
            return round($amount / $rate, 2);
        }

        return round($amount, 2);
    }

    public function currencySymbol(?string $currency = null): string
    {
        $currency = $this->normalizeCurrency($currency ?? $this->baseCurrency());
        $symbol = $this->currencySymbols()[$currency] ?? $currency;

        return trim($symbol).' ';
    }

    public function formatMoney(float $amount, ?string $currency = null): string
    {
        return $this->currencySymbol($currency).number_format($amount, 2, '.', '');
    }

    private function normalizeHexColor(?string $color, string $fallback): string
    {
        $color = strtolower(trim((string) $color));

        if (preg_match('/^#[0-9a-f]{6}$/', $color) === 1) {
            return $color;
        }

        return $fallback;
    }

    private function adjustHexBrightness(string $hex, int $amount): string
    {
        $hex = ltrim($this->normalizeHexColor($hex, '#2c1458'), '#');
        $parts = str_split($hex, 2);

        $rgb = array_map(
            fn (string $part): int => max(0, min(255, hexdec($part) + $amount)),
            $parts
        );

        return '#'.implode('', array_map(
            fn (int $value): string => str_pad(dechex($value), 2, '0', STR_PAD_LEFT),
            $rgb
        ));
    }

    private function hexToRgbString(string $hex): string
    {
        $hex = ltrim($this->normalizeHexColor($hex, '#2c1458'), '#');

        return collect(str_split($hex, 2))
            ->map(fn (string $part): int => hexdec($part))
            ->implode(', ');
    }

    private function hexToRgbaString(string $hex, float $alpha): string
    {
        return 'rgba('.$this->hexToRgbString($hex).', '.max(0, min(1, $alpha)).')';
    }

    private function normalizeSocialIcon(mixed $icon, string $label, string $url): string
    {
        $icon = trim((string) $icon);

        if (preg_match('/^bi-[a-z0-9-]+$/', $icon) === 1) {
            return $icon;
        }

        $haystack = strtolower($label.' '.$url);

        return match (true) {
            str_contains($haystack, 'facebook') => 'bi-facebook',
            str_contains($haystack, 'instagram') => 'bi-instagram',
            str_contains($haystack, 'tiktok') => 'bi-tiktok',
            str_contains($haystack, 'whatsapp') => 'bi-whatsapp',
            str_contains($haystack, 'youtube') || str_contains($haystack, 'youtu.be') => 'bi-youtube',
            str_contains($haystack, 'x.com') || str_contains($haystack, 'twitter') => 'bi-twitter-x',
            str_contains($haystack, 'linkedin') => 'bi-linkedin',
            str_contains($haystack, 'reddit') => 'bi-reddit',
            str_contains($haystack, 'tripadvisor') => 'bi-compass',
            str_contains($haystack, 'booking') => 'bi-calendar-check',
            str_contains($haystack, 'airbnb') => 'bi-house-heart',
            str_contains($haystack, 'maps.google') || str_contains($haystack, 'google.com/maps') => 'bi-geo-alt',
            str_contains($haystack, 'google') => 'bi-google',
            str_contains($haystack, 'pinterest') => 'bi-pinterest',
            str_contains($haystack, 'threads') => 'bi-threads',
            str_contains($haystack, 'telegram') => 'bi-telegram',
            str_contains($haystack, 'agoda') => 'bi-building-check',
            str_contains($haystack, 'expedia') => 'bi-airplane',
            default => 'bi-link-45deg',
        };
    }
}
