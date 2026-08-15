<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HotelSetting;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\RoomType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class RoomPublicController extends Controller
{
    public function index(): View
    {
        $hotelSetting = $this->hotelSetting();
        $roomTypes = RoomType::query()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->orderBy('base_price')
            ->orderBy('name')
            ->get()
            ->map(fn (RoomType $roomType) => $this->enrichRoomType($roomType));
        $availableRooms = Room::query()
            ->with('roomType.promotions')
            ->where('is_active', true)
            ->where('status', 'available')
            ->whereHas('roomType', fn ($query) => $query
                ->where('is_active', true)
                ->where('show_on_website', true))
            ->orderBy('number')
            ->get()
            ->map(fn (Room $room) => $this->enrichAvailableRoom($room));
        $availableRoomsByType = $availableRooms
            ->groupBy(fn (Room $room): ?int => $room->roomType?->id)
            ->map(function (Collection $rooms): array {
                $roomType = $rooms->first()?->roomType;

                return [
                    'roomType' => $roomType,
                    'rooms' => $rooms->values(),
                    'available_count' => $rooms->count(),
                ];
            })
            ->values();

        return view('public.rooms.index', [
            'hotelSetting' => $hotelSetting,
            'roomTypes' => $roomTypes,
            'availableRooms' => $availableRooms,
            'availableRoomsByType' => $availableRoomsByType,
            'title' => __('public.meta.rooms_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.rooms_description', ['hotel' => $hotelSetting->hotel_name]),
        ]);
    }

    public function show(RoomType $roomType): View
    {
        abort_unless($roomType->is_active && $roomType->show_on_website, 404);

        $hotelSetting = $this->hotelSetting();
        $roomType = $this->enrichRoomType($roomType);
        $applicablePromotions = $roomType->promotions()
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->sortByDesc(fn (Promotion $promotion): float => $promotion->calculateDiscount((float) $roomType->base_price))
            ->values();

        return view('public.rooms.show', [
            'hotelSetting' => $hotelSetting,
            'roomType' => $roomType,
            'applicablePromotions' => $applicablePromotions,
            'title' => $roomType->name.' | '.$hotelSetting->hotel_name,
            'metaDescription' => __('public.meta.room_detail_description', [
                'room' => $roomType->name,
                'hotel' => $hotelSetting->hotel_name,
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
