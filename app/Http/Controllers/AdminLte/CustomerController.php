<?php

namespace App\Http\Controllers\AdminLte;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLte\StoreCustomerRequest;
use App\Http\Requests\AdminLte\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    private const DOCUMENT_TYPES = [
        'ci' => 'Cedula de Identidad',
        'passport' => 'Pasaporte',
        'nit' => 'NIT',
        'other' => 'Otro',
    ];

    public function index(): View
    {
        $this->authorize('viewAny', Customer::class);

        $customers = Customer::query()->get();

        return view('adminlte.customers.index', [
            'documentTypes' => self::DOCUMENT_TYPES,
            'stats' => [
                'total' => $customers->count(),
                'active' => $customers->where('is_active', true)->count(),
                'foreign' => $customers->where('is_foreign', true)->count(),
                'companies' => $customers->where('is_company', true)->count(),
            ],
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $query = Customer::query()
            ->with('user')
            ->withCount(['reservations', 'payments'])
            ->select('customers.*');

        return DataTables::eloquent($query)
            ->addColumn('document_type_label', fn (Customer $customer): string => self::DOCUMENT_TYPES[$customer->document_type] ?? 'Sin documento')
            ->addColumn('birth_date_formatted', fn (Customer $customer): string => optional($customer->birth_date)?->format('d/m/Y') ?? '-')
            ->addColumn('is_foreign_label', fn (Customer $customer): string => $customer->is_foreign ? 'Extranjero' : 'Nacional')
            ->addColumn('is_company_label', fn (Customer $customer): string => $customer->is_company ? 'Empresa' : 'Persona')
            ->addColumn('status_label', fn (Customer $customer): string => $customer->is_active ? 'Activo' : 'Inactivo')
            ->addColumn('status_badge_class', fn (Customer $customer): string => $customer->is_active ? 'badge text-bg-success' : 'badge text-bg-secondary')
            ->addColumn('created_at_formatted', fn (Customer $customer): string => optional($customer->created_at)?->format('d/m/Y H:i') ?? '-')
            ->addColumn('can_update', fn (Customer $customer): bool => auth()->user()->can('update', $customer))
            ->addColumn('can_delete', fn (Customer $customer): bool => auth()->user()->can('delete', $customer))
            ->addColumn('update_url', fn (Customer $customer): string => route('adminlte.customers.update', $customer))
            ->addColumn('delete_url', fn (Customer $customer): string => route('adminlte.customers.destroy', $customer))
            ->toJson();
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorize('create', Customer::class);

        $validated = $request->validated();

        Customer::create([
            'user_id' => null,
            'full_name' => $validated['full_name'],
            'document_type' => $validated['document_type'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_foreign' => (bool) ($validated['is_foreign'] ?? false),
            'is_company' => (bool) ($validated['is_company'] ?? false),
            'company_name' => $validated['company_name'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Cliente registrado correctamente.',
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $validated = $request->validated();

        $customer->update([
            'user_id' => null,
            'full_name' => $validated['full_name'],
            'document_type' => $validated['document_type'] ?? null,
            'document_number' => $validated['document_number'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'email' => $validated['email'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'country' => $validated['country'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_foreign' => (bool) ($validated['is_foreign'] ?? false),
            'is_company' => (bool) ($validated['is_company'] ?? false),
            'company_name' => $validated['company_name'] ?? null,
            'tax_number' => $validated['tax_number'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
        ]);
    }

    public function destroy(Customer $customer): JsonResponse
    {
        $this->authorize('delete', $customer);

        // TODO: cuando exista el modulo de reservas, impedir eliminar clientes con historial asociado.
        $customer->delete();

        return response()->json([
            'message' => 'Cliente eliminado correctamente.',
        ]);
    }
}
