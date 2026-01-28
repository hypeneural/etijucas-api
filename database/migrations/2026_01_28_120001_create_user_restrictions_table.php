<?php

use App\Domain\Moderation\Enums\RestrictionScope;
use App\Domain\Moderation\Enums\RestrictionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_restrictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->index();
            $table->enum('type', RestrictionType::values())->index();
            $table->enum('scope', RestrictionScope::values())->default(RestrictionScope::Global->value);
            $table->text('reason')->nullable();
            $table->uuid('created_by')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->uuid('revoked_by')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'ends_at'], 'user_restrictions_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_restrictions');
    }
};
