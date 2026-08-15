<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            WorkShiftSeeder::class,
            HotelSettingSeeder::class,
        ]);

        $this->seedSystemUsers();
    }

    private function seedSystemUsers(): void
    {
        User::query()
            ->where('email', 'client@hotel.test')
            ->delete();

        $users = [
            [
                'name' => 'Administrador General',
                'email' => 'admin@hotel.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Gerente General',
                'email' => 'manager@hotel.test',
                'role' => 'general_manager',
            ],
            [
                'name' => 'Recepcion Principal',
                'email' => 'reception@hotel.test',
                'role' => 'receptionist',
                'work_shift' => 'Manana',
            ],
        ];

        foreach ($users as $seedUser) {
            $user = User::query()->updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'name' => $seedUser['name'],
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'work_shift_id' => $seedUser['role'] === 'receptionist'
                        ? \App\Models\WorkShift::query()->where('name', $seedUser['work_shift'])->value('id')
                        : null,
                ]
            );

            $user->syncRoles([$seedUser['role']]);
        }
    }
}
