<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = [
    [
        'name' => 'Administrador General',
        'email' => 'admin@hotel.test',
        'role' => 'admin',
    ],
    [
        'name' => 'Gerente General',
        'email' => 'manager@hotel.test',
        'role' => 'manager',
    ],
    [
        'name' => 'Recepcion Principal',
        'email' => 'reception@hotel.test',
        'role' => 'receptionist',
    ],
    [
        'name' => 'Cliente Web',
        'email' => 'client@hotel.test',
        'role' => 'client',
    ],
];

foreach ($users as $seedUser) {
    $user = App\Models\User::query()->updateOrCreate(
        ['email' => $seedUser['email']],
        [
            'name' => $seedUser['name'],
            'password' => bcrypt('password'),
            'is_active' => true,
        ]
    );

    $user->syncRoles([$seedUser['role']]);

    echo $user->email.' -> '.$seedUser['role'].PHP_EOL;
}
