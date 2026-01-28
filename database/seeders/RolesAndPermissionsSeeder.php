<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // =====================================================
        // Topics (Fórum)
        // =====================================================
        Permission::create(['name' => 'topics.create']);
        Permission::create(['name' => 'topics.update.own']);
        Permission::create(['name' => 'topics.delete.own']);
        Permission::create(['name' => 'topics.moderate']); // ocultar/remover qualquer

        // =====================================================
        // Comments
        // =====================================================
        Permission::create(['name' => 'comments.create']);
        Permission::create(['name' => 'comments.delete.own']);
        Permission::create(['name' => 'comments.moderate']);

        // =====================================================
        // Reports (Denúncias)
        // =====================================================
        Permission::create(['name' => 'reports.create']);
        Permission::create(['name' => 'reports.delete.own']);
        Permission::create(['name' => 'reports.status.update']); // moderador/admin

        // =====================================================
        // Admin Content
        // =====================================================
        Permission::create(['name' => 'events.manage']);
        Permission::create(['name' => 'phones.manage']);
        Permission::create(['name' => 'trash.manage']);
        Permission::create(['name' => 'masses.manage']);
        Permission::create(['name' => 'alerts.manage']);
        Permission::create(['name' => 'users.manage']);
        Permission::create(['name' => 'bairros.manage']);

        // =====================================================
        // Create Roles
        // =====================================================

        // User Role - Basic permissions
        $userRole = Role::create(['name' => 'user']);
        $userRole->givePermissionTo([
            'topics.create',
            'topics.update.own',
            'topics.delete.own',
            'comments.create',
            'comments.delete.own',
            'reports.create',
            'reports.delete.own',
        ]);

        // Moderator Role - User permissions + moderation
        $moderatorRole = Role::create(['name' => 'moderator']);
        $moderatorRole->givePermissionTo([
            // Inherited from user
            'topics.create',
            'topics.update.own',
            'topics.delete.own',
            'comments.create',
            'comments.delete.own',
            'reports.create',
            'reports.delete.own',
            // Moderation permissions
            'topics.moderate',
            'comments.moderate',
            'reports.status.update',
            'alerts.manage',
        ]);

        // Admin Role - All permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        $this->command->info('Roles and permissions seeded successfully!');
        $this->command->table(
            ['Role', 'Permissions'],
            [
                ['user', $userRole->permissions->pluck('name')->join(', ')],
                ['moderator', $moderatorRole->permissions->pluck('name')->join(', ')],
                ['admin', 'All permissions'],
            ]
        );
    }
}
