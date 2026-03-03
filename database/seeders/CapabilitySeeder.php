<?php

namespace Database\Seeders;

use App\Models\Capability;
use App\Support\CapabilityRegistry;
use Illuminate\Database\Seeder;

class CapabilitySeeder extends Seeder {
    /**
     * Seed all capabilities from CapabilityRegistry.
     */
    public function run(): void {
        foreach (CapabilityRegistry::all() as $module => $capabilities) {
            foreach ($capabilities as $cap) {
                Capability::updateOrCreate(
                    ['name' => $cap['name']],
                    [
                        'label'  => $cap['label'],
                        'module' => $module,
                    ]
                );
            }
        }

        $this->command->info('✅ Capabilities seeded successfully.');
    }
}
