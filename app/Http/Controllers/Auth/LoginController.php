<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\HotelSetting;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /**
     * Max failed attempts (per email + IP) before a timed lockout kicks in.
     */
    protected int $maxAttempts = 5;

    public function showLoginForm(): View|RedirectResponse
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user?->is_active) {
            return redirect()->to($this->redirectPathFor($user));
        }

        if ($user && ! $user->is_active) {
            Auth::logout();
        }

        return view('adminlte::auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            /** @var \App\Models\User|null $user */
            $user = Auth::user();

            if (! $user?->is_active) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Tu usuario esta inactivo. Contacta al administrador.',
                ])->onlyInput('email');
            }

            RateLimiter::clear($this->throttleKey($request));
            $request->session()->regenerate();

            return redirect()->to($this->redirectPathFor($user));
        }

        RateLimiter::hit($this->throttleKey($request));

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Throw a throttled validation error once too many failed attempts pile up.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')).'|'.$request->ip());
    }

    protected function redirectPathFor(User $user): string
    {
        if ($this->shouldUseCustomerPortal($user)) {
            return route('public.customer-portal.search');
        }

        if ($this->canUseManagementDashboard($user)) {
            return route('adminlte.dashboard');
        }

        if ($this->canUseFrontDesk($user)) {
            return route('adminlte.front-desk.index');
        }

        $policyRedirects = [
            ['adminlte.cash-registers.index', fn (): bool => $user->can('viewAny', CashRegister::class)],
            ['adminlte.payments.index', fn (): bool => $user->can('viewAny', Payment::class)],
            ['adminlte.reservations.index', fn (): bool => $user->can('viewAny', Reservation::class)],
            ['adminlte.customers.index', fn (): bool => $user->can('viewAny', Customer::class)],
            ['adminlte.rooms.index', fn (): bool => $user->can('viewAny', Room::class)],
            ['adminlte.room-types.index', fn (): bool => $user->can('viewAny', RoomType::class)],
            ['adminlte.promotions.index', fn (): bool => $user->can('viewAny', Promotion::class)],
            ['adminlte.settings.edit', fn (): bool => $user->can('viewAny', HotelSetting::class)],
            ['adminlte.roles.index', fn (): bool => $user->can('viewAny', Role::class)],
            ['adminlte.users.index', fn (): bool => $user->can('viewAny', User::class)],
        ];

        foreach ($policyRedirects as [$routeName, $canAccess]) {
            if ($canAccess() && $this->routeExists($routeName)) {
                return route($routeName);
            }
        }

        return $this->routeExists('public.home') ? route('public.home') : url('/');
    }

    protected function shouldUseCustomerPortal(User $user): bool
    {
        $canOnlySeeOwnHotelData = (
            $user->can('viewAny', Reservation::class) ||
            $user->can('viewAny', Payment::class) ||
            $user->can('viewAny', Customer::class)
        ) && ! (
            $this->isDashboardUser($user) ||
            $user->can('reservas.ver') ||
            $user->can('pagos.ver') ||
            $user->can('clientes.ver')
        );

        return $canOnlySeeOwnHotelData && $this->routeExists('public.customer-portal.search');
    }

    protected function canUseManagementDashboard(User $user): bool
    {
        return $user->can('dashboard.ver')
            && $this->routeExists('adminlte.dashboard')
            && $this->isDashboardUser($user);
    }

    protected function canUseFrontDesk(User $user): bool
    {
        return $this->routeExists('adminlte.front-desk.index')
            && (
                $user->can('viewAny', Reservation::class) ||
                $user->can('viewAny', Room::class)
            );
    }

    protected function routeExists(string $routeName): bool
    {
        return app('router')->has($routeName);
    }

    protected function isDashboardUser(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager', 'general_manager', 'receptionist']);
    }
}
