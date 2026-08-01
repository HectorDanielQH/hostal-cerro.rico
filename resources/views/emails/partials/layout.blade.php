@php
    $hotelSetting = \App\Models\HotelSetting::current();
    $hotelName = filled($hotelSetting->hotel_name) ? $hotelSetting->hotel_name : 'Hostal Cerro Rico';
    $hotelLocation = trim(collect([$hotelSetting->city, $hotelSetting->country])->filter()->implode(', '));
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? $hotelName }}</title>
</head>
<body style="margin:0;background:#f4f1eb;color:#1f2937;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f1eb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:22px;overflow:hidden;border:1px solid #e6ddcf;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#21113f,#5b21b6 55%,#b7791f);padding:30px 34px;color:#ffffff;">
                            <div style="font-size:12px;letter-spacing:.22em;text-transform:uppercase;opacity:.78;">{{ $eyebrow ?? $hotelName }}</div>
                            <h1 style="margin:10px 0 0;font-size:30px;line-height:1.15;font-weight:700;">{{ $heading ?? $hotelName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 34px;">
                            {{ $slot }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:22px 34px;background:#171323;color:#d9d3ea;font-size:13px;">
                            <strong>{{ $hotelName }}</strong><br>
                            Hospedaje en {{ $hotelLocation !== '' ? $hotelLocation : 'Potosi, Bolivia' }}.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
