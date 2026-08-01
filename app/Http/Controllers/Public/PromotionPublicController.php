<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\HotelSetting;
use App\Models\Promotion;
use Illuminate\Contracts\View\View;

class PromotionPublicController extends Controller
{
    public function index(): View
    {
        $hotelSetting = $this->hotelSetting();
        $promotions = Promotion::query()
            ->with(['roomTypes' => fn ($query) => $query->where('is_active', true)->where('show_on_website', true)])
            ->where('is_active', true)
            ->where('show_on_website', true)
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isCurrentlyActive())
            ->values();

        return view('public.promotions.index', [
            'hotelSetting' => $hotelSetting,
            'promotions' => $promotions,
            'title' => __('public.meta.promotions_title', ['hotel' => $hotelSetting->hotel_name]),
            'metaDescription' => __('public.meta.promotions_description', ['hotel' => $hotelSetting->hotel_name]),
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
}
