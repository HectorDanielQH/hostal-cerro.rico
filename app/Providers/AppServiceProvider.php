<?php

namespace App\Providers;

use App\Models\CashRegister;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Event;
use App\Models\HotelSetting;
use App\Models\KanbanCard;
use App\Models\Message;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Policies\CashRegisterPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\EventPolicy;
use App\Policies\HotelSettingPolicy;
use App\Policies\KanbanCardPolicy;
use App\Policies\MessagePolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\PromotionPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RolePolicy;
use App\Policies\RoomPolicy;
use App\Policies\RoomTypePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ((bool) config('app.force_https')) {
            URL::forceScheme('https');
        }

        Gate::policy(CashRegister::class, CashRegisterPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(HotelSetting::class, HotelSettingPolicy::class);
        Gate::policy(KanbanCard::class, KanbanCardPolicy::class);
        Gate::policy(Message::class, MessagePolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Promotion::class, PromotionPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(RoomType::class, RoomTypePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('is-admin', fn ($user) => $user->hasRole('admin'));
        Gate::define('is-manager', fn ($user) => $user->hasRole('manager'));
        Gate::define('is-receptionist', fn ($user) => $user->hasRole('receptionist'));
        Gate::define('is-client', fn ($user) => $user->hasRole('client'));

        Gate::define('view-security-menu', fn ($user) => $user->hasRole('admin'));
        Gate::define('view-admin-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager']));
        Gate::define('view-reception-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager', 'receptionist']));
        Gate::define('view-reception-workspace-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager', 'general_manager', 'receptionist']));
        Gate::define('view-expanded-hotel-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager', 'general_manager']));
        Gate::define('view-client-menu', fn ($user) => $user->hasRole('client'));
        Gate::define('view-reports-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager', 'general_manager']));
        Gate::define('view-website-menu', fn ($user) => $user->hasAnyRole(['admin', 'manager']));
        Gate::define('view-settings-menu', fn ($user) => $user->can('configuracion.ver'));
    }
}
