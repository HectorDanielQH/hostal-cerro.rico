<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Reports\CashRegisterReportService;
use App\Services\Reports\CustomerReportService;
use App\Services\Reports\HotelChamberReportService;
use App\Services\Reports\IncomeReportService;
use App\Services\Reports\OccupancyReportService;
use App\Services\Reports\PaymentReportService;
use App\Services\Reports\ReservationReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReservationReportService $reservationReportService,
        private readonly IncomeReportService $incomeReportService,
        private readonly PaymentReportService $paymentReportService,
        private readonly CashRegisterReportService $cashRegisterReportService,
        private readonly OccupancyReportService $occupancyReportService,
        private readonly CustomerReportService $customerReportService,
        private readonly HotelChamberReportService $hotelChamberReportService,
    ) {
    }

    public function index(): View
    {
        $this->authorize('viewAny', Reservation::class);
        abort_unless(auth()->user()->can('reportes.ver'), 403);

        $baseFilters = $this->defaultDateFilters();
        $reservationReport = $this->reservationReportService->generate($baseFilters);
        $incomeReport = $this->incomeReportService->generate($baseFilters);
        $paymentReport = $this->paymentReportService->generate($baseFilters);
        $cashRegisterReport = $this->cashRegisterReportService->generate($baseFilters);
        $occupancyReport = $this->occupancyReportService->generate($baseFilters);
        $customerReport = $this->customerReportService->generate($baseFilters);
        $hotelChamberReport = $this->hotelChamberReportService->generate($this->defaultDailyFilters());

        return view('adminlte.reports.index', [
            'filterOptions' => $this->filterOptions(),
            'reservationReport' => $reservationReport,
            'incomeReport' => $incomeReport,
            'paymentReport' => $paymentReport,
            'cashRegisterReport' => $cashRegisterReport,
            'occupancyReport' => $occupancyReport,
            'customerReport' => $customerReport,
            'hotelChamberReport' => $hotelChamberReport,
            'defaultFilters' => $baseFilters,
            'defaultDailyFilters' => $this->defaultDailyFilters(),
            'reportEndpoints' => [
                'reservations' => route('adminlte.reports.reservations'),
                'income' => route('adminlte.reports.income'),
                'payments' => route('adminlte.reports.payments'),
                'cashRegisters' => route('adminlte.reports.cash-registers'),
                'occupancy' => route('adminlte.reports.occupancy'),
                'customers' => route('adminlte.reports.customers'),
                'hotel-chamber' => route('adminlte.reports.hotel-chamber'),
            ],
            'exportEndpoints' => [
                'reservations' => route('adminlte.reports.reservations.export'),
                'income' => route('adminlte.reports.income.export'),
                'payments' => route('adminlte.reports.payments.export'),
                'cashRegisters' => route('adminlte.reports.cash-registers.export'),
                'hotel-chamber' => route('adminlte.reports.hotel-chamber.export'),
            ],
        ]);
    }

    public function reservations(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateReservationFilters($request);
        $report = $this->reservationReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.reservations', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()->can('reportes.exportar'),
        ]);
    }

    public function income(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateIncomeFilters($request);
        $report = $this->incomeReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.income', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()->can('reportes.exportar'),
        ]);
    }

    public function payments(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validatePaymentFilters($request);
        $report = $this->paymentReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.payments', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()->can('reportes.exportar'),
        ]);
    }

    public function cashRegisters(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateCashRegisterFilters($request);
        $report = $this->cashRegisterReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.cash-registers', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()->can('reportes.exportar'),
        ]);
    }

    public function occupancy(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateOccupancyFilters($request);
        $report = $this->occupancyReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.occupancy', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => false,
        ]);
    }

    public function customers(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateCustomerFilters($request);
        $report = $this->customerReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.customers', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => false,
        ]);
    }

    public function hotelChamber(Request $request): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        $filters = $this->validateHotelChamberFilters($request);
        $report = $this->hotelChamberReportService->generate($filters);

        return $this->reportResponse($request, 'adminlte.reports.partials.hotel-chamber', [
            'report' => $report,
            'filterOptions' => $this->filterOptions(),
            'canExport' => auth()->user()->can('reportes.exportar'),
        ]);
    }

    public function exportReservations(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);
        $report = $this->reservationReportService->generate($this->validateReservationFilters($request));

        return $this->csvDownload(
            'reservas_'.now()->format('Ymd').'.csv',
            ['Codigo', 'Cliente', 'Habitacion', 'Tipo', 'Entrada', 'Salida', 'Noches', 'Adultos', 'Ninos', 'Estado', 'Origen', 'Total', 'Pagado', 'Saldo', 'Creada'],
            $report['rows']->map(fn ($reservation): array => [
                $reservation->code,
                $reservation->customer?->full_name,
                $reservation->room?->number,
                $reservation->roomType?->name ?? $reservation->room?->roomType?->name,
                optional($reservation->check_in)->format('d/m/Y'),
                optional($reservation->check_out)->format('d/m/Y'),
                $reservation->nights,
                $reservation->adults,
                $reservation->children,
                $reservation->status,
                $reservation->source,
                (float) $reservation->total_amount,
                (float) $reservation->paid_amount,
                (float) $reservation->balance_amount,
                optional($reservation->created_at)->format('d/m/Y H:i'),
            ])->all()
        );
    }

    public function exportIncome(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);
        $report = $this->incomeReportService->generate($this->validateIncomeFilters($request));

        return $this->csvDownload(
            'ingresos_'.now()->format('Ymd').'.csv',
            ['Fecha', 'Ingreso confirmado'],
            collect($report['by_day'])->map(fn (array $row): array => [
                $row['date'],
                $row['amount'],
            ])->all()
        );
    }

    public function exportPayments(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);
        $report = $this->paymentReportService->generate($this->validatePaymentFilters($request));

        return $this->csvDownload(
            'pagos_'.now()->format('Ymd').'.csv',
            ['Codigo pago', 'Codigo reserva', 'Cliente', 'Monto registrado', 'Moneda', 'Aplicacion automatica', 'Metodo', 'Estado', 'Fecha pago', 'Fecha confirmacion', 'Confirmado por', 'Referencia'],
            $report['rows']->map(fn ($payment): array => [
                $payment->code,
                $payment->reservation?->code,
                $payment->customer?->full_name,
                (float) $payment->amount,
                $payment->currency,
                (float) ($payment->amount_base ?? $payment->amount),
                $payment->payment_method,
                $payment->status,
                optional($payment->payment_date)->format('d/m/Y'),
                optional($payment->confirmed_at)->format('d/m/Y H:i'),
                $payment->confirmedBy?->name,
                $payment->reference_number,
            ])->all()
        );
    }

    public function exportCashRegisters(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);
        $report = $this->cashRegisterReportService->generate($this->validateCashRegisterFilters($request));

        return $this->csvDownload(
            'caja_'.now()->format('Ymd').'.csv',
            ['Codigo', 'Usuario', 'Turno', 'Apertura', 'Cierre', 'Monto inicial', 'Ingresos', 'Egresos', 'Ajustes', 'Esperado', 'Contado', 'Diferencia', 'Estado'],
            $report['rows']->map(fn ($cashRegister): array => [
                $cashRegister->code,
                $cashRegister->user?->name,
                $cashRegister->shift_name,
                optional($cashRegister->opened_at)->format('d/m/Y H:i'),
                optional($cashRegister->closed_at)->format('d/m/Y H:i'),
                (float) $cashRegister->opening_amount,
                (float) $cashRegister->total_income,
                (float) $cashRegister->total_expense,
                (float) $cashRegister->total_adjustment,
                (float) $cashRegister->expected_amount,
                (float) $cashRegister->counted_amount,
                (float) $cashRegister->difference_amount,
                $cashRegister->status,
            ])->all()
        );
    }

    public function exportHotelChamber(Request $request)
    {
        abort_unless(auth()->user()->can('reportes.exportar'), 403);

        $filters = $this->validateHotelChamberFilters($request);

        $report = $this->hotelChamberReportService->generate($filters);

        return $this->csvDownload(
            'camara_hotelera_'.($filters['date_from'] ?? now()->toDateString()).'_'.($filters['date_to'] ?? now()->toDateString()).'.csv',
            [
                'Codigo reserva',
                'Huesped',
                'Tipo documento',
                'Numero documento',
                'Nacionalidad',
                'Pais',
                'Ciudad',
                'Telefono',
                'Correo',
                'Extranjero',
                'Habitacion',
                'Tipo habitacion',
                'Desde',
                'Hasta',
                'Check-in real',
                'Check-out real',
                'Noches reservadas',
                'Noches reales',
                'Adultos',
                'Ninos',
                'Total huespedes',
                'Estado',
                'Hospedado actualmente',
                'Se paso de salida',
                'Extension detectada',
                'Observacion',
                'Origen',
                'Solicitudes especiales',
            ],
            $report['rows']->map(fn (array $row): array => [
                $row['reservation_code'],
                $row['guest_name'],
                $row['document_type'],
                $row['document_number'],
                $row['nationality'],
                $row['country'],
                $row['city'],
                $row['phone'],
                $row['email'],
                $row['is_foreign'] ? 'Si' : 'No',
                $row['room_number'],
                $row['room_type'],
                $row['check_in'],
                $row['check_out'],
                $row['checked_in_at'],
                $row['checked_out_at'],
                $row['reserved_nights'],
                $row['actual_nights'],
                $row['adults'],
                $row['children'],
                $row['total_guests'],
                $row['status_label'],
                $row['is_currently_hosted'] ? 'Si' : 'No',
                $row['is_overstayed'] ? 'Si' : 'No',
                $row['is_extended'] ? 'Si' : 'No',
                $row['operational_observation'],
                $row['source'],
                $row['special_requests'],
            ])->all()
        );
    }

    private function filterOptions(): array
    {
        return [
            'roomTypes' => RoomType::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::query()->orderBy('full_name')->get(['id', 'full_name']),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'reservationStatuses' => [
                Reservation::STATUS_PENDING => 'Pendiente',
                Reservation::STATUS_CONFIRMED => 'Confirmada',
                Reservation::STATUS_CHECKED_IN => 'Check-in',
                Reservation::STATUS_CHECKED_OUT => 'Check-out',
                Reservation::STATUS_CANCELLED => 'Cancelada',
                Reservation::STATUS_EXPIRED => 'Expirada',
                Reservation::STATUS_NO_SHOW => 'No se presento',
            ],
            'reservationSources' => [
                'reception' => 'Recepcion',
                'website' => 'Pagina web',
                'phone' => 'Telefono',
                'whatsapp' => 'WhatsApp',
                'agency' => 'Agencia',
                'other' => 'Otro',
            ],
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'qr' => 'QR',
                'bank' => 'Deposito / Transferencia',
                'card' => 'Tarjeta',
                'other' => 'Otro',
            ],
            'paymentStatuses' => [
                Payment::STATUS_PENDING => 'Pendiente',
                Payment::STATUS_CONFIRMED => 'Confirmado',
                Payment::STATUS_REJECTED => 'Rechazado',
                Payment::STATUS_CANCELLED => 'Anulado',
                Payment::STATUS_REFUNDED => 'Devuelto',
            ],
            'cashRegisterStatuses' => [
                CashRegister::STATUS_OPEN => 'Abierta',
                CashRegister::STATUS_CLOSED => 'Cerrada',
                CashRegister::STATUS_CANCELLED => 'Anulada',
            ],
        ];
    }

    private function defaultDateFilters(): array
    {
        return [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->endOfMonth()->toDateString(),
        ];
    }

    private function defaultDailyFilters(): array
    {
        return [
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
            'lodging_status' => 'all',
        ];
    }

    private function commonDateRules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    private function validateReservationFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'status' => ['nullable', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:50'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);
    }

    private function validateIncomeFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'payment_method' => ['nullable', 'string', 'max:50'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);
    }

    private function validatePaymentFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'customer_id' => ['nullable', 'exists:customers,id'],
        ]);
    }

    private function validateCashRegisterFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'user_id' => ['nullable', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }

    private function validateOccupancyFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);
    }

    private function validateCustomerFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'nationality' => ['nullable', 'string', 'max:100'],
            'is_company' => ['nullable', 'in:0,1'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);
    }

    private function validateHotelChamberFilters(Request $request): array
    {
        return $request->validate($this->commonDateRules() + [
            'room_type_id' => ['nullable', 'exists:room_types,id'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'lodging_status' => ['nullable', 'in:all,currently_hosted,overstayed,extended,checked_out'],
        ]);
    }

    private function csvDownload(string $filename, array $headings, array $rows)
    {
        return response()->streamDownload(function () use ($headings, $rows): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headings, ';');

            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($value) => match (true) {
                    is_bool($value) => $value ? 'Si' : 'No',
                    $value instanceof \DateTimeInterface => $value->format('d/m/Y H:i'),
                    $value === null => '',
                    default => $value,
                }, $row), ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function reportResponse(Request $request, string $view, array $data): Response|\Illuminate\Http\JsonResponse|\Illuminate\Contracts\View\View
    {
        if ($request->expectsJson()) {
            return response()->json($data['report']);
        }

        return response()->view($view, $data);
    }
}
