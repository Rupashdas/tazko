<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void {
        $this->call([
            CapabilitySeeder::class, // Seed capabilities first
            RoleSeeder::class,        // Seed roles (Admin, Developer)
            SuperAdminSeeder::class,  // Seed Super Admin user with all capabilities
            UserSeeder::class,        // Seed Admin and Developer users with roles
            ProjectSeeder::class,     // Seed projects with tasks, labels, subtasks
        ]);
    }
}
