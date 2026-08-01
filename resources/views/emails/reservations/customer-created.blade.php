@php
    $roomName = $reservation->roomType?->name ?? $reservation->room?->roomType?->name ?? 'Habitacion';
@endphp

@component('emails.partials.layout', [
    'title' => 'Solicitud de reserva recibida',
    'eyebrow' => 'Reserva en revision',
    'heading' => 'Recibimos tu solicitud',
])
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Hola {{ $customer?->full_name ?? 'cliente' }}, recibimos tu solicitud de reserva
        <strong>{{ $reservation->code }}</strong>. Nuestro equipo la revisara y te contactara para confirmar los detalles.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0;border-collapse:collapse;">
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #eee;"><strong>Habitacion</strong></td>
            <td align="right" style="padding:12px 0;border-bottom:1px solid #eee;">{{ $roomName }}</td>
        </tr>
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #eee;"><strong>Entrada</strong></td>
            <td align="right" style="padding:12px 0;border-bottom:1px solid #eee;">{{ optional($reservation->check_in)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #eee;"><strong>Salida</strong></td>
            <td align="right" style="padding:12px 0;border-bottom:1px solid #eee;">{{ optional($reservation->check_out)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td style="padding:12px 0;"><strong>Total estimado</strong></td>
            <td align="right" style="padding:12px 0;">Bs. {{ number_format((float) $reservation->total_amount, 2) }}</td>
        </tr>
    </table>

    <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#5f6675;">
        Puedes consultar el estado de tu reserva y subir tu comprobante desde el siguiente enlace.
    </p>

    <p style="margin:0;">
        <a href="{{ $portalUrl }}" style="display:inline-block;background:#5b21b6;color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:999px;font-weight:700;">
            Consultar mi reserva
        </a>
    </p>
@endcomponent
