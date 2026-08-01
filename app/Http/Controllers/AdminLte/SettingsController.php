<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\UpdateHotelSettingRequest;
use App\Models\HotelSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function edit(): View
    {
        $hotelSetting = HotelSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'hotel_name' => 'Hostal Cerro Rico',
                'legal_name' => 'Hostal Cerro Rico',
                'city' => 'Potosi',
                'country' => 'Bolivia',
                'currency' => 'BOB',
                'enabled_currencies' => HotelSetting::defaultCurrencies(),
                'usd_exchange_rate' => 6.96,
                'theme_primary_color' => '#2c1458',
                'theme_secondary_color' => '#c6811e',
                'theme_accent_color' => '#d66a55',
                'theme_background_color' => '#f4f0e8',
                'theme_surface_color' => '#fcfaf7',
                'theme_text_color' => '#14293f',
                'theme_muted_color' => '#667789',
                'is_active' => true,
            ]
        );

        $this->authorize('view', $hotelSetting);

        return view('adminlte.settings.edit', compact('hotelSetting'));
    }

    public function update(UpdateHotelSettingRequest $request): RedirectResponse|JsonResponse
    {
        $hotelSetting = HotelSetting::query()->firstOrCreate(['id' => 1]);
        $this->authorize('update', $hotelSetting);

        $validated = $request->validated();

        $validated['logo'] = $this->syncUpload(
            $request,
            'logo',
            $hotelSetting->logo,
            'hotel/logos',
            (bool) ($validated['clear_logo'] ?? false)
        );
        $validated['favicon'] = $this->syncUpload(
            $request,
            'favicon',
            $hotelSetting->favicon,
            'hotel/favicons',
            (bool) ($validated['clear_favicon'] ?? false)
        );
        $validated['cover_image'] = $this->syncUpload(
            $request,
            'cover_image',
            $hotelSetting->cover_image,
            'hotel/covers',
            (bool) ($validated['clear_cover_image'] ?? false)
        );
        $validated['hero_video'] = $this->syncUpload(
            $request,
            'hero_video',
            $hotelSetting->hero_video,
            'hotel/videos',
            (bool) ($validated['clear_hero_video'] ?? false)
        );
        $validated['mobile_hero_image'] = $this->syncUpload(
            $request,
            'mobile_hero_image',
            $hotelSetting->mobile_hero_image,
            'hotel/mobile-hero',
            (bool) ($validated['clear_mobile_hero_image'] ?? false)
        );
        $validated['payment_qr_image'] = $this->syncUpload(
            $request,
            'payment_qr_image',
            $hotelSetting->payment_qr_image,
            'hotel/payments',
            (bool) ($validated['clear_payment_qr_image'] ?? false)
        );
        $validated['digital_wallet_qr_image'] = $this->syncUpload(
            $request,
            'digital_wallet_qr_image',
            $hotelSetting->digital_wallet_qr_image,
            'hotel/payments',
            (bool) ($validated['clear_digital_wallet_qr_image'] ?? false)
        );
        $validated['bank_qr_image'] = $this->syncUpload(
            $request,
            'bank_qr_image',
            $hotelSetting->bank_qr_image,
            'hotel/payments',
            (bool) ($validated['clear_bank_qr_image'] ?? false)
        );
        $validated['hero_video_url'] = (bool) ($validated['clear_hero_video_url'] ?? false)
            ? null
            : ($validated['hero_video_url'] ?? null);
        $validated['contact_people'] = $this->normalizeContactPeople($request, $hotelSetting, $validated['contact_people'] ?? []);
        $validated['contact_emails'] = $this->normalizeContactEmails($validated['contact_emails'] ?? []);
        $validated['social_links'] = $this->normalizeSocialLinks($validated['social_links'] ?? []);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? $hotelSetting->is_active ?? true);
        unset(
            $validated['clear_logo'],
            $validated['clear_favicon'],
            $validated['clear_cover_image'],
            $validated['clear_hero_video'],
            $validated['clear_hero_video_url'],
            $validated['clear_mobile_hero_image'],
            $validated['clear_payment_qr_image'],
            $validated['clear_digital_wallet_qr_image'],
            $validated['clear_bank_qr_image'],
            $validated['currency_base_code']
        );

        $hotelSetting->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Configuracion actualizada correctamente.',
            ]);
        }

        return redirect()->back()->with('success', 'Configuracion actualizada correctamente.');
    }

    private function syncUpload(UpdateHotelSettingRequest $request, string $field, ?string $oldPath, string $directory, bool $shouldClear = false): ?string
    {
        if ($shouldClear && $oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
            $oldPath = null;
        }

        if (! $request->hasFile($field)) {
            return $oldPath;
        }

        $newPath = $request->file($field)->store($directory, 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }

    private function normalizeSocialLinks(array $links): array
    {
        return collect($links)
            ->filter(fn (mixed $link): bool => is_array($link) && filled($link['url'] ?? null))
            ->map(function (array $link): array {
                $label = trim((string) ($link['label'] ?? ''));
                $url = trim((string) ($link['url'] ?? ''));
                $icon = trim((string) ($link['icon'] ?? ''));

                return [
                    'label' => $label !== '' ? $label : $this->guessSocialLabel($url),
                    'url' => $url,
                    'icon' => preg_match('/^bi-[a-z0-9-]+$/', $icon) === 1 ? $icon : $this->guessSocialIcon($url, $label),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeContactPeople(UpdateHotelSettingRequest $request, HotelSetting $hotelSetting, array $people): array
    {
        $existingPhotos = collect($hotelSetting->contact_people ?? [])
            ->filter(fn (mixed $person): bool => is_array($person) && filled($person['photo'] ?? null))
            ->pluck('photo')
            ->all();

        $keptPhotos = [];

        $normalized = collect($people)
            ->filter(fn (mixed $person): bool => is_array($person))
            ->map(function (array $person, int|string $index) use ($request, &$keptPhotos): ?array {
                $name = trim((string) ($person['name'] ?? ''));
                $role = trim((string) ($person['role'] ?? ''));
                $countryCode = preg_replace('/\D+/', '', (string) ($person['country_code'] ?? ''));
                $phone = preg_replace('/\D+/', '', (string) ($person['phone'] ?? ''));
                $existingPhoto = trim((string) ($person['existing_photo'] ?? ''));
                $clearPhoto = filter_var($person['clear_photo'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $photo = $clearPhoto ? '' : $existingPhoto;

                if ($request->hasFile("contact_people.$index.photo")) {
                    $photo = $request->file("contact_people.$index.photo")->store('hotel/contact-people', 'public');
                }

                if ($phone === '' && $name === '' && $role === '' && $photo === '') {
                    return null;
                }

                if ($countryCode === '') {
                    $countryCode = '591';
                }

                if ($photo !== '') {
                    $keptPhotos[] = $photo;
                }

                return [
                    'name' => $name !== '' ? $name : 'Recepcion',
                    'role' => $role !== '' ? $role : 'Atencion al cliente',
                    'country_code' => $countryCode,
                    'phone' => $phone,
                    'photo' => $photo,
                ];
            })
            ->filter()
            ->values()
            ->all();

        collect($existingPhotos)
            ->diff($keptPhotos)
            ->each(function (string $photo): void {
                if (Storage::disk('public')->exists($photo)) {
                    Storage::disk('public')->delete($photo);
                }
            });

        return $normalized;
    }

    private function normalizeContactEmails(array $emails): array
    {
        return collect($emails)
            ->filter(fn (mixed $contact): bool => is_array($contact) && filled($contact['email'] ?? null))
            ->map(function (array $contact): array {
                $label = trim((string) ($contact['label'] ?? ''));
                $email = trim((string) ($contact['email'] ?? ''));

                return [
                    'label' => $label !== '' ? $label : 'Contacto',
                    'email' => $email,
                ];
            })
            ->values()
            ->all();
    }

    private function guessSocialLabel(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        $host = is_string($host) ? preg_replace('/^www\./', '', strtolower($host)) : '';

        return match (true) {
            str_contains($host, 'facebook') => 'Facebook',
            str_contains($host, 'instagram') => 'Instagram',
            str_contains($host, 'tiktok') => 'TikTok',
            str_contains($host, 'whatsapp') => 'WhatsApp',
            str_contains($host, 'youtube') || str_contains($host, 'youtu.be') => 'YouTube',
            str_contains($host, 'x.com') || str_contains($host, 'twitter') => 'X',
            str_contains($host, 'linkedin') => 'LinkedIn',
            str_contains($host, 'reddit') => 'Reddit',
            str_contains($host, 'tripadvisor') => 'Tripadvisor',
            str_contains($host, 'booking') => 'Booking',
            str_contains($host, 'airbnb') => 'Airbnb',
            str_contains($host, 'maps.google') || str_contains($host, 'google.com') => 'Google Maps',
            str_contains($host, 'pinterest') => 'Pinterest',
            str_contains($host, 'threads') => 'Threads',
            str_contains($host, 'telegram') => 'Telegram',
            str_contains($host, 'agoda') => 'Agoda',
            str_contains($host, 'expedia') => 'Expedia',
            default => 'Red social',
        };
    }

    private function guessSocialIcon(string $url, string $label = ''): string
    {
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
