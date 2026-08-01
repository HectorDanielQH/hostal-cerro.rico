@php
    $roomName = $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? 'Habitacion';
@endphp

@component('emails.partials.layout', [
    'title' => 'Reserva confirmada',
    'eyebrow' => 'Reserva aprobada',
    'heading' => 'Tu reserva fue confirmada',
])
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Hola {{ $customer?->full_name ?? 'cliente' }}, tu reserva
        <strong>{{ $reservation->code }}</strong> ya fue confirmada por el hotel.
    </p>

    <div style="background:#f8f5ef;border:1px solid #eadfce;border-radius:18px;padding:18px;margin:22px 0;">
        <p style="margin:0 0 8px;"><strong>Habitacion:</strong> {{ $roomName }}</p>
        <p style="margin:0 0 8px;"><strong>Entrada:</strong> {{ optional($reservation->check_in)->format('d/m/Y') }}</p>
        <p style="margin:0 0 8px;"><strong>Salida:</strong> {{ optional($reservation->check_out)->format('d/m/Y') }}</p>
        <p style="margin:0;"><strong>Saldo:</strong> Bs. {{ number_format((float) $reservation->balance_amount, 2) }}</p>
    </div>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#5f6675;">
        Guarda este correo y revisa tu panel si necesitas subir un comprobante o consultar el estado de la reserva.
    </p>

    <p style="margin:0;">
        <a href="{{ $portalUrl }}" style="display:inline-block;background:#15803d;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:999px;font-weight:700;">
            Ver estado de reserva
        </a>
    </p>
@endcomponent
