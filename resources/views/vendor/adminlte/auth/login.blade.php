@extends('adminlte::auth.auth-master', ['authType' => 'login'])

@section('auth_body')
    @php($hotelSetting = \App\Models\HotelSetting::current())

    <div class="hotel-auth-card-header">
        <span>Panel interno</span>
        <h2>Iniciar sesion</h2>
    </div>

    <form action="{{ route('login') }}" method="post">
        @csrf

        <label class="form-label fw-bold" for="email">Correo electronico</label>
        <div class="input-group mb-3">
            <input type="email" name="email" value="{{ old('email') }}"
                   id="email"
                   class="form-control @error('email') is-invalid @enderror"
                   placeholder="usuario@hotel.com" autocomplete="email" required autofocus>
            <div class="input-group-text"><span class="bi bi-envelope"></span></div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <label class="form-label fw-bold" for="password">Contrasena</label>
        <div class="input-group mb-3">
            <input type="password" name="password"
                   id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Ingresa tu contrasena" autocomplete="current-password" required>
            <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>

        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div class="form-check">
                <input type="checkbox" name="remember" value="1" class="form-check-input" id="remember" @checked(old('remember'))>
                <label class="form-check-label" for="remember">Recordar este equipo</label>
            </div>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="hotel-auth-link small">Olvide mi contrasena</a>
            @endif
        </div>

        <button type="submit" class="btn hotel-auth-submit w-100">
            <i class="bi bi-box-arrow-in-right me-2" aria-hidden="true"></i> Entrar al sistema
        </button>
    </form>
@endsection
