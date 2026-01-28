<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Topics Table
        Schema::create('topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general'); // general, complaint, praise, question
            $table->boolean('pinned')->default(false);
            $table->boolean('locked')->default(false);
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('type');
            $table->index('created_at');
        });

        // Comments Table
        Schema::create('comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            // Polymorphic relation (to allow comments on other things in future)
            $table->uuid('commentable_id');
            $table->string('commentable_type');
            $table->text('content');
            $table->softDeletes();
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id']);
        });

        // Topic Likes (Pivot)
        Schema::create('topic_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('topic_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'topic_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topic_likes');
        Schema::dropIfExists('comments');
        Schema::dropIfExists('topics');
    }
};
