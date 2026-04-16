<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    /**
     * Seed users with their roles.
     * Super Admin is seeded separately by SuperAdminSeeder.
     */
    public function run(): void {
        $this->seedAdminUsers();
        $this->seedDeveloperUsers();
    }

    private function seedAdminUsers(): void {
        $adminRole = Role::where('name', 'admin')->first();

        $adminsData = [
            [
                'name'     => 'Rupash Das',
                'email'    => 'rupash.das.202@gmail.com',
                'title'    => 'Senior Programmer',
                'password' => Hash::make('Pass123#'),
            ],
            [
                'name'     => 'Prottasha Das',
                'email'    => 'prottasha@gmail.com',
                'title'    => 'Big Boss',
                'password' => Hash::make('Pass123#'),
            ],
        ];

        foreach ($adminsData as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'              => $userData['name'],
                    'title'             => $userData['title'],
                    'password'          => $userData['password'],
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            $user->roles()->syncWithoutDetaching([$adminRole->id]);

            $user->preference()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'palette'     => 'ember',
                    'appearance'  => 'os',
                    'timezone'    => 'UTC',
                    'week_start'  => 'monday',
                    'time_format' => '24',
                ]
            );
        }

        $this->command->info("✅ Created " . count($adminsData) . " admin users.");
    }

    private function seedDeveloperUsers(): void {
        $developerRole = Role::where('name', 'developer')->first();

        $developersData = [
            [
                'name'     => 'Debos Das',
                'email'    => 'debos.das.02@gmail.com',
                'title'    => 'Backend Developer',
                'password' => Hash::make('Pass123#'),
            ],
            [
                'name'     => 'Nishan Das',
                'email'    => 'nishandas880@gmail.com',
                'title'    => 'Frontend Developer',
                'password' => Hash::make('Pass123#'),
            ],
            [
                'name'     => 'Tanjim Ahmmed',
                'email'    => 'tanjimahmmed@gmail.com',
                'title'    => 'Fullstack Developer',
                'password' => Hash::make('Pass123#'),
            ],
        ];

        foreach ($developersData as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'              => $userData['name'],
                    'title'             => $userData['title'],
                    'password'          => $userData['password'],
                    'email_verified_at' => now(),
                    'is_active'         => true,
                ]
            );

            $user->roles()->syncWithoutDetaching([$developerRole->id]);

            $user->preference()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'palette'     => 'ember',
                    'appearance'  => 'os',
                    'timezone'    => 'UTC',
                    'week_start'  => 'monday',
                    'time_format' => '24',
                ]
            );
        }

        $this->command->info("✅ Created " . count($developersData) . " developer users.");
    }
}