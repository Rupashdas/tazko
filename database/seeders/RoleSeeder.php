<?php

namespace Database\Seeders;

use App\Models\Capability;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder {
    /**
     * Seed Admin and Developer roles with appropriate capabilities.
     * Super Admin is seeded separately by SuperAdminSeeder.
     */
    public function run(): void {
        $this->seedAdminRole();
        $this->seedDeveloperRole();
    }

    private function seedAdminRole(): void {
        $role = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'label'          => 'Admin',
                'is_system_role' => false,
            ]
        );

        $capabilities = [
            // Users - manage users but not role assignment (super admin only)
            'users.view',
            'users.create',
            'users.update',
            'users.activate',
            'users.profile.view',
            'users.profile.update',
            'users.password.change',

            // Roles - can manage roles but not delete system roles
            'roles.view',
            'roles.create',
            'roles.update',
            'permissions.view',

            // Projects - full access
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
            'projects.archive',
            'projects.restore',
            'projects.members.manage',

            // Tasks - full access
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.delete',
            'tasks.archive',
            'tasks.restore',
            'tasks.assign',
            'tasks.status.update',
            'tasks.priority.update',
            'tasks.deadline.update',
            'tasks.subtask.manage',
            'tasks.dependency.manage',
            'tasks.reorder',
            'tasks.bulk.update',

            // Comments - full access
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.delete',
            'comments.mention',
            'comments.react',

            // Files - full access
            'files.view',
            'files.upload',
            'files.delete',
            'files.version.manage',

            // Time - full access
            'time.start',
            'time.stop',
            'time.manual.entry',
            'time.view.own',
            'time.view.all',
            'time.export',
            'time.approve',

            // Notifications - full access
            'notifications.view',
            'notifications.mark.read',
            'notifications.settings.manage',

            // Activity - full access
            'activity.view',
            'activity.export',

            // Reports - full access
            'reports.view',
            'reports.project',
            'reports.task',
            'reports.user',
            'reports.time',
            'reports.export.pdf',
            'reports.export.excel',

            // Clients - full access
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'clients.project.view',

            // Search - full access
            'search.global',
            'search.saved.filter.manage',

            // Settings - manage most settings but not system-level
            'settings.view',
            'settings.update',
            'settings.branding.manage',
            'settings.email.manage',
            'settings.language.manage',

            // System - NO ACCESS (super admin only)
        ];

        $capabilityIds = Capability::whereIn('name', $capabilities)->pluck('id');
        $role->capabilities()->sync($capabilityIds);

        $this->command->info("✅ Admin role synced with {$capabilityIds->count()} capabilities.");
    }

    private function seedDeveloperRole(): void {
        $role = Role::updateOrCreate(
            ['name' => 'developer'],
            [
                'label'          => 'Developer',
                'is_system_role' => false,
            ]
        );

        $capabilities = [
            // Users - own profile only
            'users.profile.view',
            'users.profile.update',
            'users.password.change',

            // Projects - create and update but not delete/archive/restore
            'projects.view',
            'projects.create',
            'projects.update',

            // Tasks - create and update but limited delete/archive
            'tasks.view',
            'tasks.create',
            'tasks.update',
            'tasks.assign',
            'tasks.status.update',
            'tasks.priority.update',
            'tasks.deadline.update',
            'tasks.subtask.manage',
            'tasks.dependency.manage',
            'tasks.reorder',
            'tasks.bulk.update',

            // Comments - create and update own, view all
            'comments.view',
            'comments.create',
            'comments.update',
            'comments.mention',

            // Files - upload and delete own files
            'files.view',
            'files.upload',
            'files.delete',

            // Time - track own time
            'time.start',
            'time.stop',
            'time.manual.entry',
            'time.view.own',

            // Notifications - view and mark read
            'notifications.view',
            'notifications.mark.read',

            // Activity - view only
            'activity.view',

            // Reports - basic reports only
            'reports.view',
            'reports.project',
            'reports.task',

            // Clients - view only
            'clients.view',
            'clients.project.view',

            // Search - global search
            'search.global',

            // Settings - view and language only
            'settings.view',
            'settings.language.manage',

            // System - NO ACCESS
        ];

        $capabilityIds = Capability::whereIn('name', $capabilities)->pluck('id');
        $role->capabilities()->sync($capabilityIds);

        $this->command->info("✅ Developer role synced with {$capabilityIds->count()} capabilities.");
    }
}