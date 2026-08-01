@php
    $roomName = $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? 'Habitacion';
    $sourceLabel = $reservation->source === 'website' ? 'Web publica' : 'Recepcion';
@endphp

@component('emails.partials.layout', [
    'title' => 'Nueva reserva',
    'eyebrow' => 'Accion requerida',
    'heading' => 'Tienes nueva reserva',
])
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Hola {{ $recipient->name }}, ingreso una nueva reserva que necesita revision del equipo.
    </p>

    <div style="background:#f8f5ef;border:1px solid #eadfce;border-radius:18px;padding:18px;margin:22px 0;">
        <p style="margin:0 0 8px;"><strong>Codigo:</strong> {{ $reservation->code }}</p>
        <p style="margin:0 0 8px;"><strong>Cliente:</strong> {{ $customer?->full_name ?? 'Sin cliente' }}</p>
        <p style="margin:0 0 8px;"><strong>Habitacion:</strong> {{ $roomName }}</p>
        <p style="margin:0 0 8px;"><strong>Entrada:</strong> {{ optional($reservation->check_in)->format('d/m/Y') }}</p>
        <p style="margin:0 0 8px;"><strong>Salida:</strong> {{ optional($reservation->check_out)->format('d/m/Y') }}</p>
        <p style="margin:0;"><strong>Origen:</strong> {{ $sourceLabel }}</p>
    </div>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#5f6675;">
        Revisa disponibilidad, datos del cliente y forma de pago preferida antes de confirmar.
    </p>

    <p style="margin:0;">
        <a href="{{ $adminUrl }}" style="display:inline-block;background:#5b21b6;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:999px;font-weight:700;">
            Revisar reserva
        </a>
    </p>
@endcomponent
