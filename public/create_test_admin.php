<?php

/**
 * Script para criar usuário admin de teste em produção.
 * Acesse: https://api.natalemtijucas.com.br/create_test_admin.php
 * IMPORTANTE: Delete após o uso!
 */

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "<h1>Create Test Admin User</h1>";
echo "<pre>";

try {
    $email = 'admin@teste.com';
    $phone = '48999999999';

    // Check if user already exists
    $existingUser = \App\Models\User::where('email', $email)->orWhere('phone', $phone)->first();

    if ($existingUser) {
        echo "User already exists!\n";
        echo "ID: " . $existingUser->id . "\n";
        echo "Email: " . $existingUser->email . "\n";
        echo "Phone: " . $existingUser->phone . "\n";

        // Generate new token
        $token = $existingUser->createToken('test-token')->plainTextToken;
        echo "\n=== NEW AUTH TOKEN ===\n";
        echo $token . "\n";
        echo "======================\n";
    } else {
        // Create new admin user
        $user = \App\Models\User::create([
            'nome' => 'Admin Teste',
            'email' => $email,
            'phone' => $phone,
            'phone_verified_at' => now(),
            'email_verified_at' => now(),
            'password' => bcrypt('Admin@123'),
        ]);

        // Assign admin role
        $user->assignRole('admin');

        echo "User created successfully!\n";
        echo "ID: " . $user->id . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Phone: " . $user->phone . "\n";
        echo "Role: admin\n";

        // Generate token
        $token = $user->createToken('test-token')->plainTextToken;
        echo "\n=== AUTH TOKEN ===\n";
        echo $token . "\n";
        echo "==================\n";
    }

    echo "\n\nUse this token in Authorization header:\n";
    echo "Authorization: Bearer <token>\n";

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
