<?php

declare(strict_types=1);

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

        $permissions = [
            // Topics (Forum)
            'topics.create',
            'topics.update.own',
            'topics.delete.own',
            'topics.moderate',
            // Comments
            'comments.create',
            'comments.delete.own',
            'comments.moderate',
            // Reports (Denuncias)
            'reports.create',
            'reports.delete.own',
            'reports.status.update',
            // Admin Content
            'events.manage',
            'phones.manage',
            'trash.manage',
            'masses.manage',
            'alerts.manage',
            'users.manage',
            'bairros.manage',
            // Moderacao
            'flags.manage',
            'restrictions.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // User Role - Basic permissions
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $userRole->syncPermissions([
            'topics.create',
            'topics.update.own',
            'topics.delete.own',
            'comments.create',
            'comments.delete.own',
            'reports.create',
            'reports.delete.own',
        ]);

        // Moderator Role - User permissions + moderation
        $moderatorRole = Role::firstOrCreate(['name' => 'moderator']);
        $moderatorRole->syncPermissions([
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
            'flags.manage',
            'restrictions.manage',
        ]);

        // Admin Role - All permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

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
