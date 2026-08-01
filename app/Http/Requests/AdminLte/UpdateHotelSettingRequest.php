<?php

namespace App\Http\Requests\AdminLte;

use App\Models\HotelSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class UpdateHotelSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('configuracion.editar');
    }

    public function rules(): array
    {
        return [
            'hotel_name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'slogan' => ['nullable', 'string', 'max:255'],
            'description_short' => ['nullable', 'string', 'max:500'],
            'description_long' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'clear_logo' => ['nullable', 'boolean'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,jpg,jpeg,webp,svg', 'max:1024'],
            'clear_favicon' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'clear_cover_image' => ['nullable', 'boolean'],
            'theme_primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_secondary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_background_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_surface_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_text_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'theme_muted_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'hero_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'],
            'clear_hero_video' => ['nullable', 'boolean'],
            'hero_video_url' => ['nullable', 'url', 'max:1000'],
            'clear_hero_video_url' => ['nullable', 'boolean'],
            'mobile_hero_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'clear_mobile_hero_image' => ['nullable', 'boolean'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'contact_people' => ['nullable', 'array', 'max:20'],
            'contact_people.*.name' => ['nullable', 'string', 'max:100'],
            'contact_people.*.role' => ['nullable', 'string', 'max:100'],
            'contact_people.*.country_code' => ['nullable', 'string', 'max:8'],
            'contact_people.*.phone' => ['nullable', 'string', 'max:30'],
            'contact_people.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'contact_people.*.existing_photo' => ['nullable', 'string', 'max:255'],
            'contact_people.*.clear_photo' => ['nullable', 'boolean'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_emails' => ['nullable', 'array', 'max:20'],
            'contact_emails.*.label' => ['nullable', 'string', 'max:100'],
            'contact_emails.*.email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'social_links' => ['nullable', 'array', 'max:20'],
            'social_links.*.label' => ['nullable', 'string', 'max:80'],
            'social_links.*.url' => ['nullable', 'url', 'max:500'],
            'social_links.*.icon' => ['nullable', 'string', 'max:60', 'regex:/^bi-[a-z0-9-]+$/'],
            'google_maps_url' => ['nullable', 'url'],
            'currency' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]{2,10}$/'],
            'currency_base_code' => ['nullable', 'string', 'max:10'],
            'enabled_currencies' => ['nullable', 'array'],
            'enabled_currencies.*.code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]{2,10}$/'],
            'enabled_currencies.*.name' => ['nullable', 'string', 'max:80'],
            'enabled_currencies.*.symbol' => ['nullable', 'string', 'max:20'],
            'enabled_currencies.*.is_base' => ['nullable', 'boolean'],
            'tax_name' => ['nullable', 'string', 'max:255'],
            'tax_number' => ['nullable', 'string', 'max:100'],
            'payment_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'clear_payment_qr_image' => ['nullable', 'boolean'],
            'digital_wallet_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'clear_digital_wallet_qr_image' => ['nullable', 'boolean'],
            'bank_qr_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'clear_bank_qr_image' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'payment_instructions' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $currencies = $this->normalizeCurrencies($this->input('enabled_currencies', []), $this->input('currency_base_code'));
        $baseCurrency = collect($currencies)->firstWhere('is_base', true)['code'] ?? 'BOB';

        $this->merge([
            'currency' => $baseCurrency,
            'enabled_currencies' => $currencies,
            'theme_primary_color' => $this->normalizeColor($this->input('theme_primary_color'), '#2c1458'),
            'theme_secondary_color' => $this->normalizeColor($this->input('theme_secondary_color'), '#c6811e'),
            'theme_accent_color' => $this->normalizeColor($this->input('theme_accent_color'), '#d66a55'),
            'theme_background_color' => $this->normalizeColor($this->input('theme_background_color'), '#f4f0e8'),
            'theme_surface_color' => $this->normalizeColor($this->input('theme_surface_color'), '#fcfaf7'),
            'theme_text_color' => $this->normalizeColor($this->input('theme_text_color'), '#14293f'),
            'theme_muted_color' => $this->normalizeColor($this->input('theme_muted_color'), '#667789'),
            'clear_logo' => $this->boolean('clear_logo'),
            'clear_favicon' => $this->boolean('clear_favicon'),
            'clear_cover_image' => $this->boolean('clear_cover_image'),
            'clear_hero_video' => $this->boolean('clear_hero_video'),
            'clear_hero_video_url' => $this->boolean('clear_hero_video_url'),
            'clear_mobile_hero_image' => $this->boolean('clear_mobile_hero_image'),
            'clear_payment_qr_image' => $this->boolean('clear_payment_qr_image'),
            'clear_digital_wallet_qr_image' => $this->boolean('clear_digital_wallet_qr_image'),
            'clear_bank_qr_image' => $this->boolean('clear_bank_qr_image'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'hotel_name' => 'nombre comercial',
            'legal_name' => 'razon social',
            'description_short' => 'descripcion corta',
            'description_long' => 'descripcion larga',
            'theme_primary_color' => 'color principal',
            'theme_secondary_color' => 'color secundario',
            'theme_accent_color' => 'color de acento',
            'theme_background_color' => 'color de fondo',
            'theme_surface_color' => 'color de tarjetas',
            'theme_text_color' => 'color de texto',
            'theme_muted_color' => 'color de texto secundario',
            'hero_video' => 'video del encabezado',
            'hero_video_url' => 'enlace de video del encabezado',
            'mobile_hero_image' => 'imagen del encabezado para moviles',
            'favicon' => 'favicon',
            'phone' => 'telefono',
            'contact_people.*.name' => 'nombre de contacto WhatsApp',
            'contact_people.*.role' => 'cargo o area de contacto WhatsApp',
            'contact_people.*.country_code' => 'codigo de pais',
            'contact_people.*.phone' => 'celular de contacto WhatsApp',
            'contact_people.*.photo' => 'foto de contacto WhatsApp',
            'contact_emails.*.label' => 'nombre o area del correo',
            'contact_emails.*.email' => 'correo de contacto',
            'social_links.*.label' => 'nombre de red social',
            'social_links.*.url' => 'URL de red social',
            'social_links.*.icon' => 'icono de red social',
            'google_maps_url' => 'Google Maps URL',
            'currency' => 'moneda base',
            'enabled_currencies.*.code' => 'codigo de moneda',
            'enabled_currencies.*.name' => 'nombre de moneda',
            'enabled_currencies.*.symbol' => 'simbolo de moneda',
            'digital_wallet_qr_image' => 'QR de billetera digital',
            'bank_qr_image' => 'QR de banco local',
        ];
    }

    public function messages(): array
    {
        return [
            'currency.required' => 'Selecciona la moneda base para contabilizar.',
            'currency.regex' => 'La moneda base debe usar un codigo corto. Ejemplo: BOB, USD, EUR.',
            'enabled_currencies.*.code.regex' => 'Cada moneda debe tener un codigo corto. Ejemplo: BOB, USD, EUR.',
            'theme_primary_color.regex' => 'El color principal debe estar en formato HEX. Ejemplo: #2c1458.',
            'theme_secondary_color.regex' => 'El color secundario debe estar en formato HEX. Ejemplo: #c6811e.',
            'theme_accent_color.regex' => 'El color de acento debe estar en formato HEX. Ejemplo: #d66a55.',
            'theme_background_color.regex' => 'El color de fondo debe estar en formato HEX. Ejemplo: #f4f0e8.',
            'theme_surface_color.regex' => 'El color de tarjetas debe estar en formato HEX. Ejemplo: #fcfaf7.',
            'theme_text_color.regex' => 'El color de texto debe estar en formato HEX. Ejemplo: #14293f.',
            'theme_muted_color.regex' => 'El color de texto secundario debe estar en formato HEX. Ejemplo: #667789.',
            'social_links.*.icon.regex' => 'El icono de red social debe usar una clase de Bootstrap Icons. Ejemplo: bi-facebook.',
        ];
    }

    private function normalizeCurrencies(mixed $currencies, mixed $baseCurrency = null): array
    {
        $rows = collect(is_array($currencies) ? $currencies : [])
            ->filter(fn (mixed $currency): bool => is_array($currency))
            ->map(function (array $currency): array {
                $code = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($currency['code'] ?? '')));
                $name = trim((string) ($currency['name'] ?? ''));
                $symbol = trim((string) ($currency['symbol'] ?? ''));

                return [
                    'code' => substr($code, 0, 10),
                    'name' => $name !== '' ? $name : substr($code, 0, 10),
                    'symbol' => $symbol !== '' ? $symbol : substr($code, 0, 10),
                    'is_base' => (bool) ($currency['is_base'] ?? false),
                ];
            })
            ->filter(fn (array $currency): bool => $currency['code'] !== '')
            ->unique('code')
            ->values();

        if ($rows->isEmpty()) {
            $rows = collect(HotelSetting::defaultCurrencies());
        }

        $requestedBase = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', (string) $baseCurrency));
        $base = $rows->contains(fn (array $currency): bool => $currency['code'] === $requestedBase)
            ? $requestedBase
            : ($rows->firstWhere('is_base', true)['code'] ?? $rows->first()['code']);

        $rows = $rows->map(fn (array $currency): array => [
            ...$currency,
            'is_base' => $currency['code'] === $base,
        ]);

        return $rows->all();
    }

    private function normalizeColor(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return $fallback;
        }

        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return strtolower($value);
    }
}
