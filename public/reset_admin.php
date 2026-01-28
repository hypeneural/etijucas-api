<?php

/**
 * Script para resetar senha do admin em produção.
 * Acesse: https://api.natalemtijucas.com.br/reset_admin.php
 * IMPORTANTE: Delete após o uso!
 */

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "<h1>Reset Admin Password</h1>";
echo "<pre>";

try {
    // Find admin users
    $admins = \App\Models\User::role('admin')->get();

    echo "=== ADMINS ENCONTRADOS ===\n";
    if ($admins->isEmpty()) {
        echo "Nenhum admin encontrado!\n\n";

        // Create new admin
        echo "Criando novo admin...\n";
        $bairro = \App\Models\Bairro::first();

        $user = \App\Models\User::create([
            'nome' => 'Super Admin',
            'email' => 'admin@natalemtijucas.com.br',
            'phone' => '48900000000',
            'bairro_id' => $bairro?->id,
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'password' => bcrypt('SuperAdmin@2026'),
        ]);

        $user->assignRole('admin');

        echo "Admin criado!\n";
        echo "Email: admin@natalemtijucas.com.br\n";
        echo "Senha: SuperAdmin@2026\n";
    } else {
        foreach ($admins as $admin) {
            echo "ID: {$admin->id}\n";
            echo "Nome: {$admin->nome}\n";
            echo "Email: {$admin->email}\n";
            echo "Phone: {$admin->phone}\n";
            echo "Roles: " . $admin->roles->pluck('name')->join(', ') . "\n";
            echo "---\n";
        }

        // Reset password for first admin
        $firstAdmin = $admins->first();
        $newPassword = 'SuperAdmin@2026';
        $firstAdmin->update([
            'password' => bcrypt($newPassword),
            'email' => 'admin@natalemtijucas.com.br',
        ]);

        echo "\n=== SENHA RESETADA ===\n";
        echo "Email: admin@natalemtijucas.com.br\n";
        echo "Senha: {$newPassword}\n";
    }

    echo "\n=== USE ESTAS CREDENCIAIS PARA LOGIN ===\n";
    echo "URL: https://api.natalemtijucas.com.br/admin/login\n";
    echo "Email: admin@natalemtijucas.com.br\n";
    echo "Senha: SuperAdmin@2026\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
echo "<p style='color:red;font-weight:bold'>⚠️ DELETE ESTE ARQUIVO APÓS O USO!</p>";
