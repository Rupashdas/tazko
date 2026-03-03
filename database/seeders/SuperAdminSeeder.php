<?php

namespace Database\Seeders;

use App\Models\Capability;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder {
    /**
     * Create the super-admin role, assign all capabilities to it,
     * and create the super-admin user linked to that role.
     */
    public function run(): void {
        // 1. Create (or find) the super-admin system role
        $role = Role::updateOrCreate(
            ['name' => 'super-admin'],
            [
                'label'          => 'Super Admin',
                'is_system_role' => true,
            ]
        );

        // 2. Attach ALL capabilities to the super-admin role
        $allCapabilityIds = Capability::pluck('id');
        $role->capabilities()->sync($allCapabilityIds);

        $this->command->info("✅ Super Admin role synced with {$allCapabilityIds->count()} capabilities.");

        // 3. Create (or update) the super-admin user
        $user = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('password1234'),
                'email_verified_at' => now(),
            ]
        );

        // 4. Assign the super-admin role to the user
        $user->roles()->syncWithoutDetaching([$role->id]);

        // 5. Create default user preferences if not already exists
        $user->preferences()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'palette'     => 'ember',
                'appearance'  => 'os',
                'timezone'    => 'UTC',
                'week_start'  => 'sunday',
                'time_format' => '12',
            ]
        );

        $this->command->info('✅ Super Admin user → admin@example.com / password1234');
    }
}
