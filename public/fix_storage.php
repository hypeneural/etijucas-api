<?php

/**
 * Script utilitário para corrigir o symlink do storage em servidores onde SSH não está disponível.
 * USO: Acesse https://seu-dominio.com/fix_storage.php
 * IMPORTANTE: Delete este arquivo após o uso por segurança.
 */

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrapping the application
$app->make(Kernel::class)->bootstrap();

echo "<h1>Storage Link Fixer</h1>";
echo "<pre>";

try {
    // 1. Force removal of existing link/folder just in case
    $linkPath = public_path('storage');
    if (file_exists($linkPath)) {
        echo "Found existing 'storage' in public. Attempting to remove... ";
        // Helper to remove directory or file
        if (is_link($linkPath)) {
            unlink($linkPath);
            echo "Unlinked symlink.\n";
        } elseif (is_dir($linkPath)) {
            // Very simpler recursive delete for directory if it was created wrong
            // BE CAREFUL: Only do this if you are sure it's trash. 
            // Better to rename it to be safe.
            rename($linkPath, $linkPath . '_backup_' . time());
            echo "Renamed existing directory to " . basename($linkPath) . '_backup_' . time() . "\n";
        } else {
            unlink($linkPath);
            echo "Deleted file.\n";
        }
    }

    // 2. Run the command
    echo "Running 'php artisan storage:link'...\n";
    Illuminate\Support\Facades\Artisan::call('storage:link');

    echo "Output:\n";
    echo Illuminate\Support\Facades\Artisan::output();

    echo "\n---------------------------------------------------\n";
    echo "VERIFICATION:\n";
    echo "Public Path: " . public_path('storage') . "\n";
    echo "Target Path: " . storage_path('app/public') . "\n";

    if (is_link(public_path('storage'))) {
        echo "STATUS: SUCCESS! Symlink created.";
    } else {
        echo "STATUS: FAILED. Symlink not found.";
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
