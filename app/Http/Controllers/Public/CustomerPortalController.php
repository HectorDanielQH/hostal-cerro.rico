<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\CancelPublicReservationRequest;
use App\Http\Requests\Public\FindReservationRequest;
use App\Models\HotelSetting;
use App\Models\Reservation;
use App\Services\Reservations\ReservationExpirationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerPortalController extends Controller
{
    private const SESSION_KEY = 'customer_portal_reservation_code';

    public function search(): View
    {
        $hotelSetting = $this->hotelSetting();

        return view('public.customer-portal.search', [
            'hotelSetting' => $hotelSetting,
            'title' => __('public.meta.portal_search_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.portal_search_description', ['hotel' => $hotelSetting->hotel_name]),
        ]);
    }

    public function find(FindReservationRequest $request): RedirectResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();

        $validated = $request->validated();

        $reservation = Reservation::query()
            ->with('customer')
            ->where('code', trim((string) $validated['code']))
            ->first();

        if (! $reservation || ! $reservation->customer || ! $this->contactMatches($reservation, (string) $validated['contact'])) {
            return back()
                ->withInput($request->safe()->only(['code', 'contact']))
                ->withErrors(['contact' => __('public.messages.booking_not_found')]);
        }

        session([self::SESSION_KEY => $reservation->code]);

        return redirect()->route('public.customer-portal.show', $reservation->code);
    }

    public function show(Request $request, Reservation $reservation): View|RedirectResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();
        $reservation->refresh();

        if ($request->hasValidSignature()) {
            session([self::SESSION_KEY => $reservation->code]);
        }

        if (! $this->hasPortalAccess($reservation)) {
            return redirect()
                ->route('public.customer-portal.search')
                ->withErrors(['access' => __('public.messages.validate_access_show')]);
        }

        $reservation->loadMissing(['customer', 'room.roomType', 'roomType', 'promotion', 'payments']);
        $hotelSetting = $this->hotelSetting();

        return view('public.customer-portal.show', [
            'hotelSetting' => $hotelSetting,
            'reservation' => $reservation,
            'title' => __('public.meta.portal_show_title', ['code' => $reservation->code, 'hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.portal_show_description', ['hotel' => $hotelSetting->hotel_name]),
        ]);
    }

    public function cancel(CancelPublicReservationRequest $request, Reservation $reservation): RedirectResponse
    {
        app(ReservationExpirationService::class)->expirePendingReservations();
        $reservation->refresh();

        if (! $this->hasPortalAccess($reservation)) {
            return redirect()
                ->route('public.customer-portal.search')
                ->withErrors(['access' => __('public.messages.validate_access_manage')]);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($reservation, $validated): void {
            $reservation->refresh();

            if ($reservation->status !== Reservation::STATUS_PENDING) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => __('public.messages.only_pending_cancel'),
                ]);
            }

            $reservation->update([
                'status' => Reservation::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            ]);

            $room = $reservation->room;

            if ($room && ! $this->roomHasOtherActiveReservations($room->id, $reservation->id)) {
                $room->update(['status' => 'available']);
            }
        });

        session()->forget(self::SESSION_KEY);

        return redirect()
            ->route('public.customer-portal.search')
            ->with('success', __('public.messages.booking_cancelled'));
    }

    private function hotelSetting(): HotelSetting
    {
        return HotelSetting::query()->first() ?? new HotelSetting([
            'hotel_name' => 'Hostal Cerro Rico',
            'city' => 'Potosi',
            'country' => 'Bolivia',
            'currency' => 'BOB',
            'is_active' => true,
        ]);
    }

    private function hasPortalAccess(Reservation $reservation): bool
    {
        return session(self::SESSION_KEY) === $reservation->code;
    }

    private function contactMatches(Reservation $reservation, string $contact): bool
    {
        $customer = $reservation->customer;

        if (! $customer) {
            return false;
        }

        $rawContact = trim($contact);
        $normalizedContact = $this->normalizePhone($rawContact);

        $customerEmail = Str::lower(trim((string) ($customer->email ?? '')));
        $customerWhatsapp = $this->normalizePhone((string) ($customer->whatsapp ?? ''));
        $customerPhone = $this->normalizePhone((string) ($customer->phone ?? ''));

        return Str::lower($rawContact) === $customerEmail
            || ($normalizedContact !== '' && ($normalizedContact === $customerWhatsapp || $normalizedContact === $customerPhone));
    }

    private function normalizePhone(string $value): string
    {
        return preg_replace('/[\s\+\-\(\)]+/', '', $value) ?? '';
    }

    private function roomHasOtherActiveReservations(int $roomId, int $ignoreReservationId): bool
    {
        return Reservation::query()
            ->where('room_id', $roomId)
            ->where('id', '!=', $ignoreReservationId)
            ->whereIn('status', Reservation::ACTIVE_STATUSES)
            ->exists();
    }
}
