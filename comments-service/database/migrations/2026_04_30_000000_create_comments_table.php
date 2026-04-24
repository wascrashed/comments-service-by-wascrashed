<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->unsignedBigInteger('replyto_id')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->string('path')->index();
            $table->unsignedSmallInteger('level')->default(1);
            $table->string('status')->default('published');
            $table->text('body');
            $table->unsignedInteger('children_count')->default(0);
            $table->timestamps();

            $table->unique(['post_id', 'path']);
            $table->index(['post_id', 'replyto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
