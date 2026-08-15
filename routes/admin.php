<?php

use App\Http\Controllers\AdminLte\DashboardController;
use App\Http\Controllers\AdminLte\AnnouncementController;
use App\Http\Controllers\AdminLte\CashRegisterController;
use App\Http\Controllers\AdminLte\CustomerController;
use App\Http\Controllers\AdminLte\FrontDeskController;
use App\Http\Controllers\AdminLte\HostingController;
use App\Http\Controllers\AdminLte\NotificationController;
use App\Http\Controllers\AdminLte\PromotionController;
use App\Http\Controllers\AdminLte\PaymentController;
use App\Http\Controllers\AdminLte\ProfileController;
use App\Http\Controllers\AdminLte\ReportController;
use App\Http\Controllers\AdminLte\ReservationController;
use App\Http\Controllers\AdminLte\RoleController;
use App\Http\Controllers\AdminLte\RoomController;
use App\Http\Controllers\AdminLte\RoomTypeController;
use App\Http\Controllers\AdminLte\SettingsController;
use App\Http\Controllers\AdminLte\UserController;
use App\Http\Controllers\AdminLte\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('admin')
    ->name('adminlte.')
    ->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware(['permission:dashboard.ver', 'role:admin|manager|general_manager'])
            ->name('dashboard');

        Route::prefix('profile')
            ->name('profile.')
            ->group(function (): void {
                Route::get('/', [ProfileController::class, 'show'])
                    ->middleware('permission:perfil.ver')
                    ->name('show');

                Route::put('/', [ProfileController::class, 'update'])
                    ->middleware('permission:perfil.editar')
                    ->name('update');

                Route::put('password', [ProfileController::class, 'updatePassword'])
                    ->middleware('permission:perfil.editar')
                    ->name('password.update');

                Route::post('avatar', [ProfileController::class, 'updateAvatar'])
                    ->middleware('permission:perfil.editar')
                    ->name('avatar.update');

                Route::put('sessions/logout-others', [ProfileController::class, 'logoutOtherDevices'])
                    ->middleware('permission:perfil.editar')
                    ->name('sessions.logout-others');

                Route::delete('/', [ProfileController::class, 'destroy'])
                    ->middleware('permission:perfil.editar')
                    ->name('destroy');
            });

        Route::prefix('notifications')
            ->name('notifications.')
            ->group(function (): void {
                Route::get('/', [NotificationController::class, 'index'])
                    ->name('index');

                Route::put('{id}/read', [NotificationController::class, 'read'])
                    ->name('read');

                Route::put('read-all', [NotificationController::class, 'readAll'])
                    ->name('read-all');

                Route::delete('{id}', [NotificationController::class, 'destroy'])
                    ->name('destroy');
            });

        Route::get('settings', [SettingsController::class, 'edit'])
            ->middleware('permission:configuracion.ver')
            ->name('settings.edit');

        Route::put('settings', [SettingsController::class, 'update'])
            ->middleware('permission:configuracion.editar')
            ->name('settings.update');

        Route::get('hosting', [HostingController::class, 'index'])
            ->middleware('role:admin')
            ->name('hosting.index');

        Route::post('hosting/run', [HostingController::class, 'run'])
            ->middleware('role:admin')
            ->name('hosting.run');

        Route::prefix('announcements')
            ->name('announcements.')
            ->group(function (): void {
                Route::get('/', [AnnouncementController::class, 'index'])
                    ->middleware('permission:configuracion.ver')
                    ->name('index');

                Route::get('data', [AnnouncementController::class, 'data'])
                    ->middleware('permission:configuracion.ver')
                    ->name('data');

                Route::post('/', [AnnouncementController::class, 'store'])
                    ->middleware('permission:configuracion.editar')
                    ->name('store');

                Route::post('{announcement}', [AnnouncementController::class, 'update'])
                    ->middleware('permission:configuracion.editar')
                    ->name('update');

                Route::delete('{announcement}', [AnnouncementController::class, 'destroy'])
                    ->middleware('permission:configuracion.editar')
                    ->name('destroy');
            });

        Route::prefix('users')
            ->name('users.')
            ->group(function (): void {
                Route::get('/', [UserController::class, 'index'])
                    ->middleware('permission:usuarios.ver')
                    ->name('index');

                Route::get('data', [UserController::class, 'data'])
                    ->middleware('permission:usuarios.ver')
                    ->name('data');

                Route::post('/', [UserController::class, 'store'])
                    ->middleware('permission:usuarios.crear')
                    ->name('store');

                Route::put('{user}', [UserController::class, 'update'])
                    ->middleware('permission:usuarios.editar')
                    ->name('update');

                Route::delete('{user}', [UserController::class, 'destroy'])
                    ->middleware('permission:usuarios.eliminar')
                    ->name('destroy');
            });

        Route::prefix('work-shifts')
            ->name('work-shifts.')
            ->group(function (): void {
                Route::get('/', [WorkShiftController::class, 'index'])
                    ->middleware('permission:usuarios.ver')
                    ->name('index');

                Route::get('data', [WorkShiftController::class, 'data'])
                    ->middleware('permission:usuarios.ver')
                    ->name('data');

                Route::post('/', [WorkShiftController::class, 'store'])
                    ->middleware('permission:usuarios.crear')
                    ->name('store');

                Route::put('{workShift}', [WorkShiftController::class, 'update'])
                    ->middleware('permission:usuarios.editar')
                    ->name('update');

                Route::delete('{workShift}', [WorkShiftController::class, 'destroy'])
                    ->middleware('permission:usuarios.eliminar')
                    ->name('destroy');
            });

        Route::prefix('roles')
            ->name('roles.')
            ->group(function (): void {
                Route::get('/', [RoleController::class, 'index'])
                    ->middleware('permission:roles.ver')
                    ->name('index');

                Route::get('data', [RoleController::class, 'data'])
                    ->middleware('permission:roles.ver')
                    ->name('data');

                Route::post('/', [RoleController::class, 'store'])
                    ->middleware('permission:roles.crear')
                    ->name('store');

                Route::put('{role}', [RoleController::class, 'update'])
                    ->middleware('permission:roles.editar')
                    ->name('update');

                Route::delete('{role}', [RoleController::class, 'destroy'])
                    ->middleware('permission:roles.eliminar')
                    ->name('destroy');
            });

        Route::prefix('room-types')
            ->name('room-types.')
            ->group(function (): void {
                Route::get('/', [RoomTypeController::class, 'index'])
                    ->middleware('permission:tipos_habitacion.ver')
                    ->name('index');

                Route::get('data', [RoomTypeController::class, 'data'])
                    ->middleware('permission:tipos_habitacion.ver')
                    ->name('data');

                Route::post('/', [RoomTypeController::class, 'store'])
                    ->middleware('permission:tipos_habitacion.crear')
                    ->name('store');

                Route::put('{roomType}', [RoomTypeController::class, 'update'])
                    ->middleware('permission:tipos_habitacion.editar')
                    ->name('update');

                Route::delete('{roomType}', [RoomTypeController::class, 'destroy'])
                    ->middleware('permission:tipos_habitacion.eliminar')
                    ->name('destroy');
            });

        Route::prefix('rooms')
            ->name('rooms.')
            ->group(function (): void {
                Route::get('/', [RoomController::class, 'index'])
                    ->middleware('permission:habitaciones.ver')
                    ->name('index');

                Route::get('data', [RoomController::class, 'data'])
                    ->middleware('permission:habitaciones.ver')
                    ->name('data');

                Route::post('/', [RoomController::class, 'store'])
                    ->middleware('permission:habitaciones.crear')
                    ->name('store');

                Route::put('{room}', [RoomController::class, 'update'])
                    ->middleware('permission:habitaciones.editar')
                    ->name('update');

                Route::patch('{room}/status', [RoomController::class, 'changeStatus'])
                    ->middleware('permission:habitaciones.estado')
                    ->name('status');

                Route::delete('{room}', [RoomController::class, 'destroy'])
                    ->middleware('permission:habitaciones.eliminar')
                    ->name('destroy');
            });

        Route::prefix('promotions')
            ->name('promotions.')
            ->group(function (): void {
                Route::get('/', [PromotionController::class, 'index'])
                    ->middleware('permission:promociones.ver')
                    ->name('index');

                Route::get('data', [PromotionController::class, 'data'])
                    ->middleware('permission:promociones.ver')
                    ->name('data');

                Route::post('/', [PromotionController::class, 'store'])
                    ->middleware('permission:promociones.crear')
                    ->name('store');

                Route::put('{promotion}', [PromotionController::class, 'update'])
                    ->middleware('permission:promociones.editar')
                    ->name('update');

                Route::delete('{promotion}', [PromotionController::class, 'destroy'])
                    ->middleware('permission:promociones.eliminar')
                    ->name('destroy');

                Route::post('preview', [PromotionController::class, 'preview'])
                    ->middleware('permission:promociones.ver')
                    ->name('preview');
            });

        Route::prefix('customers')
            ->name('customers.')
            ->group(function (): void {
                Route::get('/', [CustomerController::class, 'index'])
                    ->middleware('permission:clientes.ver')
                    ->name('index');

                Route::get('data', [CustomerController::class, 'data'])
                    ->middleware('permission:clientes.ver')
                    ->name('data');

                Route::post('/', [CustomerController::class, 'store'])
                    ->middleware('permission:clientes.crear')
                    ->name('store');

                Route::put('{customer}', [CustomerController::class, 'update'])
                    ->middleware('permission:clientes.editar')
                    ->name('update');

                Route::delete('{customer}', [CustomerController::class, 'destroy'])
                    ->middleware('permission:clientes.eliminar')
                    ->name('destroy');
            });

        Route::prefix('reservations')
            ->name('reservations.')
            ->group(function (): void {
                Route::get('/', [ReservationController::class, 'index'])
                    ->middleware('permission:reservas.ver')
                    ->name('index');

                Route::get('data', [ReservationController::class, 'data'])
                    ->middleware('permission:reservas.ver')
                    ->name('data');

                Route::get('customer-search', [ReservationController::class, 'customerSearch'])
                    ->middleware('permission:reservas.ver')
                    ->name('customer-search');

                Route::get('agenda', [ReservationController::class, 'agenda'])
                    ->middleware('permission:reservas.ver')
                    ->name('agenda');

                Route::post('/', [ReservationController::class, 'store'])
                    ->middleware('permission:reservas.crear')
                    ->name('store');

                Route::put('{reservation}', [ReservationController::class, 'update'])
                    ->middleware('permission:reservas.editar')
                    ->name('update');

                Route::post('{reservation}/confirm', [ReservationController::class, 'confirm'])
                    ->middleware('permission:reservas.confirmar')
                    ->name('confirm');

                Route::post('{reservation}/cancel', [ReservationController::class, 'cancel'])
                    ->middleware('permission:reservas.cancelar')
                    ->name('cancel');

                Route::post('{reservation}/check-in', [ReservationController::class, 'checkIn'])
                    ->middleware('permission:reservas.checkin')
                    ->name('check-in');

                Route::post('{reservation}/check-out', [ReservationController::class, 'checkOut'])
                    ->middleware('permission:reservas.checkout')
                    ->name('check-out');

                Route::post('quote', [ReservationController::class, 'quote'])
                    ->middleware('permission:reservas.ver')
                    ->name('quote');

                Route::get('available-rooms', [ReservationController::class, 'availableRooms'])
                    ->middleware('permission:reservas.ver')
                    ->name('available-rooms');
            });

        Route::prefix('payments')
            ->name('payments.')
            ->group(function (): void {
                Route::get('/', [PaymentController::class, 'index'])
                    ->middleware('permission:pagos.ver|pagos.ver_propios')
                    ->name('index');

                Route::get('data', [PaymentController::class, 'data'])
                    ->middleware('permission:pagos.ver|pagos.ver_propios')
                    ->name('data');

                Route::get('reservation-search', [PaymentController::class, 'reservationSearch'])
                    ->middleware('permission:pagos.ver|pagos.ver_propios')
                    ->name('reservation-search');

                Route::post('/', [PaymentController::class, 'store'])
                    ->middleware('permission:pagos.crear|pagos.realizar')
                    ->name('store');

                Route::put('{payment}', [PaymentController::class, 'update'])
                    ->middleware('permission:pagos.crear|pagos.confirmar|pagos.cambiar_monto')
                    ->name('update');

                Route::post('{payment}/confirm', [PaymentController::class, 'confirm'])
                    ->middleware('permission:pagos.confirmar')
                    ->name('confirm');

                Route::post('{payment}/reject', [PaymentController::class, 'reject'])
                    ->middleware('permission:pagos.rechazar')
                    ->name('reject');

                Route::post('{payment}/cancel', [PaymentController::class, 'cancel'])
                    ->middleware('permission:pagos.anular')
                    ->name('cancel');

                Route::post('{payment}/refund', [PaymentController::class, 'refund'])
                    ->middleware('permission:pagos.devolver')
                    ->name('refund');

                Route::get('{payment}/receipt', [PaymentController::class, 'showReceipt'])
                    ->middleware('permission:pagos.ver|pagos.ver_propios')
                    ->name('receipt');
            });

        Route::prefix('cash-registers')
            ->name('cash-registers.')
            ->group(function (): void {
                Route::get('/', [CashRegisterController::class, 'index'])
                    ->middleware('permission:caja.ver')
                    ->name('index');

                Route::get('data', [CashRegisterController::class, 'data'])
                    ->middleware('permission:caja.ver')
                    ->name('data');

                Route::get('current', [CashRegisterController::class, 'current'])
                    ->middleware('permission:caja.ver')
                    ->name('current');

                Route::post('open', [CashRegisterController::class, 'open'])
                    ->middleware('permission:caja.abrir')
                    ->name('open');

                Route::post('movements', [CashRegisterController::class, 'storeMovement'])
                    ->middleware('permission:caja.ajustar')
                    ->name('movements.store');

                Route::get('{cashRegister}/movements', [CashRegisterController::class, 'movementsData'])
                    ->middleware('permission:caja.ver')
                    ->name('movements');

                Route::get('{cashRegister}/arqueo', [CashRegisterController::class, 'arqueo'])
                    ->middleware('permission:caja.arqueo')
                    ->name('arqueo');

                Route::post('{cashRegister}/close', [CashRegisterController::class, 'close'])
                    ->middleware('permission:caja.cerrar')
                    ->name('close');
            });

        Route::prefix('front-desk')
            ->name('front-desk.')
            ->group(function (): void {
                Route::get('/', [FrontDeskController::class, 'index'])
                    ->middleware('permission:reservas.ver|habitaciones.ver')
                    ->name('index');

                Route::get('summary', [FrontDeskController::class, 'summary'])
                    ->middleware('permission:reservas.ver|habitaciones.ver')
                    ->name('summary');

                Route::get('arrivals', [FrontDeskController::class, 'arrivalsData'])
                    ->middleware('permission:reservas.ver')
                    ->name('arrivals');

                Route::get('departures', [FrontDeskController::class, 'departuresData'])
                    ->middleware('permission:reservas.ver')
                    ->name('departures');

                Route::get('occupied', [FrontDeskController::class, 'occupiedData'])
                    ->middleware('permission:reservas.ver')
                    ->name('occupied');

                Route::get('rooms-status', [FrontDeskController::class, 'roomsStatusData'])
                    ->middleware('permission:habitaciones.ver')
                    ->name('rooms-status');

                Route::get('customers/{customer}/summary', [FrontDeskController::class, 'customerSummary'])
                    ->middleware('permission:reservas.ver|clientes.ver')
                    ->name('customers.summary');

                Route::put('reservations/{reservation}/guests', [FrontDeskController::class, 'updateReservationGuests'])
                    ->middleware('permission:reservas.editar|clientes.editar')
                    ->name('reservations.guests');

                Route::post('reservations/{reservation}/check-in', [FrontDeskController::class, 'checkIn'])
                    ->middleware('permission:reservas.checkin')
                    ->name('check-in');

                Route::post('reservations/{reservation}/check-out', [FrontDeskController::class, 'checkOut'])
                    ->middleware('permission:reservas.checkout')
                    ->name('check-out');

                Route::post('reservations/{reservation}/extend', [FrontDeskController::class, 'extendStay'])
                    ->middleware('permission:reservas.editar')
                    ->name('reservations.extend');

                Route::put('reservations/{reservation}/dates', [FrontDeskController::class, 'updateReservationDates'])
                    ->middleware('permission:reservas.editar')
                    ->name('reservations.dates');

                Route::post('reservations/{reservation}/cancellation-review', [FrontDeskController::class, 'reviewCancellation'])
                    ->middleware('permission:reservas.ver')
                    ->name('reservations.cancellation-review');

                Route::patch('rooms/{room}/status', [FrontDeskController::class, 'updateRoomStatus'])
                    ->middleware('permission:habitaciones.estado')
                    ->name('rooms.status');
            });

        Route::prefix('reports')
            ->name('reports.')
            ->group(function (): void {
                Route::get('/', [ReportController::class, 'index'])
                    ->middleware('permission:reportes.ver')
                    ->name('index');

                Route::get('reservations', [ReportController::class, 'reservations'])
                    ->middleware('permission:reportes.ver')
                    ->name('reservations');

                Route::get('income', [ReportController::class, 'income'])
                    ->middleware('permission:reportes.ver')
                    ->name('income');

                Route::get('payments', [ReportController::class, 'payments'])
                    ->middleware('permission:reportes.ver')
                    ->name('payments');

                Route::get('cash-registers', [ReportController::class, 'cashRegisters'])
                    ->middleware('permission:reportes.ver')
                    ->name('cash-registers');

                Route::get('occupancy', [ReportController::class, 'occupancy'])
                    ->middleware('permission:reportes.ver')
                    ->name('occupancy');

                Route::get('customers', [ReportController::class, 'customers'])
                    ->middleware('permission:reportes.ver')
                    ->name('customers');

                Route::get('hotel-chamber', [ReportController::class, 'hotelChamber'])
                    ->middleware('permission:reportes.ver')
                    ->name('hotel-chamber');

                Route::get('reservations/export', [ReportController::class, 'exportReservations'])
                    ->middleware('permission:reportes.exportar')
                    ->name('reservations.export');

                Route::get('income/export', [ReportController::class, 'exportIncome'])
                    ->middleware('permission:reportes.exportar')
                    ->name('income.export');

                Route::get('payments/export', [ReportController::class, 'exportPayments'])
                    ->middleware('permission:reportes.exportar')
                    ->name('payments.export');

                Route::get('cash-registers/export', [ReportController::class, 'exportCashRegisters'])
                    ->middleware('permission:reportes.exportar')
                    ->name('cash-registers.export');

                Route::get('hotel-chamber/export', [ReportController::class, 'exportHotelChamber'])
                    ->middleware('permission:reportes.exportar')
                    ->name('hotel-chamber.export');
            });
    });
