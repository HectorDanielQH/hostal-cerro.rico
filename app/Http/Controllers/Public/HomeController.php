<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\HotelSetting;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    public function index(): View
    {
        $hotelSetting = $this->hotelSetting();
        $featuredRoomTypes = RoomType::query()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->orderBy('base_price')
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn (RoomType $roomType) => $this->enrichRoomType($roomType));

        $activePromotions = Promotion::query()
            ->with('roomTypes')
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->take(3)
            ->values();

        $availableRoomsCount = Room::query()
            ->where('is_active', true)
            ->where('status', 'available')
            ->count();
        $availableRooms = Room::query()
            ->with('roomType.promotions')
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereHas('roomType', fn ($query) => $query
                ->where('is_active', true)
                ->where('show_on_website', true))
            ->orderBy('number')
            ->limit(18)
            ->get()
            ->map(fn (Room $room) => $this->enrichAvailableRoom($room));
        $availableRoomsByType = $availableRooms
            ->groupBy(fn (Room $room): string => (string) ($room->roomType?->id ?? 'other'))
            ->map(fn (Collection $rooms): array => [
                'roomType' => $rooms->first()?->roomType,
                'rooms' => $rooms->take(4)->values(),
                'available_count' => $rooms->count(),
            ])
            ->values();

        return view('public.home', [
            'hotelSetting' => $hotelSetting,
            'activeAnnouncements' => $this->activeAnnouncements(),
            'featuredRoomTypes' => $featuredRoomTypes,
            'availableRooms' => $availableRooms,
            'availableRoomsByType' => $availableRoomsByType,
            'activePromotions' => $activePromotions,
            'availableRoomsCount' => $availableRoomsCount,
            'activeRoomTypesCount' => RoomType::query()->where('is_active', true)->where('show_on_website', true)->count(),
            'activePromotionsCount' => $activePromotions->count(),
            'title' => __('public.meta.home_title', ['hotel' => $hotelSetting->hotel_name, 'city' => $hotelSetting->city]),
            'metaDescription' => $hotelSetting->description_short ?: __('public.meta.home_description', [
                'hotel' => $hotelSetting->hotel_name,
                'city' => $hotelSetting->city,
                'country' => $hotelSetting->country,
            ]),
        ]);
    }

    public function contact(): View
    {
        $hotelSetting = $this->hotelSetting();

        return view('public.contact', [
            'hotelSetting' => $hotelSetting,
            'title' => __('public.meta.contact_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.contact_description', [
                'hotel' => $hotelSetting->hotel_name,
                'city' => $hotelSetting->city,
                'country' => $hotelSetting->country,
            ]),
        ]);
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

    private function activeAnnouncements(): Collection
    {
        if (! Schema::hasTable('announcements')) {
            return collect();
        }

        return Announcement::query()->visibleOnWebsite()->get();
    }

    private function enrichRoomType(RoomType $roomType): RoomType
    {
        $promotion = $this->bestPromotionForRoomType($roomType);
        $roomType->setAttribute('available_rooms_count', $roomType->rooms()->where('is_active', true)->where('status', 'available')->count());
        $roomType->setAttribute('public_promotion', $promotion);
        $roomType->setAttribute('public_discount_amount', $promotion ? $promotion->calculateDiscount((float) $roomType->base_price) : 0);
        $roomType->setAttribute('public_final_price', $promotion ? $promotion->calculateFinalPrice((float) $roomType->base_price) : (float) $roomType->base_price);
        $priceUsd = $roomType->priceUsd();
        $discountRatio = (float) $roomType->base_price > 0
            ? (float) $roomType->public_final_price / (float) $roomType->base_price
            : 1;
        $roomType->setAttribute('public_final_price_usd', $priceUsd > 0 ? round($priceUsd * $discountRatio, 2) : 0);
        $roomType->setAttribute('public_discount_amount_usd', $priceUsd > 0 ? round(max($priceUsd - (float) $roomType->public_final_price_usd, 0), 2) : 0);

        return $roomType;
    }

    private function bestPromotionForRoomType(RoomType $roomType): ?Promotion
    {
        return $roomType->promotions()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->sortByDesc(fn (Promotion $promotion): float => $promotion->calculateDiscount((float) $roomType->base_price))
            ->first();
    }

    private function enrichAvailableRoom(Room $room): Room
    {
        $roomType = $this->enrichRoomType($room->roomType);

        $room->setRelation('roomType', $roomType);
        $room->setAttribute('public_gallery_images', $room->publicGalleryImages());
        $room->setAttribute('public_price', $roomType->public_final_price);

        return $room;
    }
}
