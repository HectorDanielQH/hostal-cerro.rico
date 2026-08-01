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

        return view('public.home', [
            'hotelSetting' => $hotelSetting,
            'activeAnnouncements' => $this->activeAnnouncements(),
            'featuredRoomTypes' => $featuredRoomTypes,
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
}
