@php
    $reason = $payment->rejection_reason ?: $payment->cancellation_reason ?: $payment->refund_reason;
    $isPositive = $statusContext === \App\Models\Payment::STATUS_CONFIRMED;
    $buttonColor = $isPositive ? '#15803d' : '#b45309';
@endphp

@component('emails.partials.layout', [
    'title' => $statusLabel,
    'eyebrow' => 'Estado de comprobante',
    'heading' => $statusLabel,
])
    <p style="margin:0 0 18px;font-size:16px;line-height:1.6;">
        Hola {{ $customer?->full_name ?? 'cliente' }}, actualizamos el estado del comprobante
        <strong>{{ $payment->code }}</strong> vinculado a tu reserva
        <strong>{{ $reservation?->code ?? 'sin codigo' }}</strong>.
    </p>

    <div style="background:#f8f5ef;border:1px solid #eadfce;border-radius:18px;padding:18px;margin:22px 0;">
        <p style="margin:0 0 8px;"><strong>Estado:</strong> {{ $statusLabel }}</p>
        <p style="margin:0 0 8px;"><strong>Monto:</strong> {{ $payment->currency ?? 'BOB' }} {{ number_format((float) $payment->amount, 2) }}</p>
        <p style="margin:0 0 8px;"><strong>Metodo:</strong> {{ ucfirst((string) $payment->payment_method) }}</p>
        @if ($reason)
            <p style="margin:0;"><strong>Observacion:</strong> {{ $reason }}</p>
        @endif
    </div>

    @if ($isPositive)
        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#5f6675;">
            Gracias. El pago fue aprobado y se aplicara al saldo de tu reserva segun corresponda.
        </p>
    @else
        <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#5f6675;">
            Por favor revisa la observacion del hotel. Si corresponde, puedes coordinar con recepcion o subir un nuevo comprobante.
        </p>
    @endif

    <p style="margin:0;">
        <a href="{{ $portalUrl }}" style="display:inline-block;background:{{ $buttonColor }};color:#ffffff;text-decoration:none;padding:13px 22px;border-radius:999px;font-weight:700;">
            Consultar mi reserva
        </a>
    </p>
@endcomponent
