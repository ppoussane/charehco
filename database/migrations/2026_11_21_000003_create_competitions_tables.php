<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('prize')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('deadline_at');
            $table->timestamp('results_at')->nullable();
            $table->boolean('anonymous_judging')->default(true);
            $table->boolean('is_published')->default(false);
            $table->boolean('show_on_home')->default(false);
            $table->timestamps();
        });

        Schema::create('competition_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('description', 500)->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('accepted_types')->default('pdf');
            $table->unsignedSmallInteger('max_size_mb')->default(20);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('competition_jury', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['competition_id', 'user_id']);
        });

        Schema::create('competition_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 12)->unique();
            $table->string('title');
            $table->text('statement')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['competition_id', 'user_id']);
        });

        Schema::create('entry_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competition_requirement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->unique(['competition_entry_id', 'competition_requirement_id'], 'entry_requirement_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entry_documents');
        Schema::dropIfExists('competition_entries');
        Schema::dropIfExists('competition_jury');
        Schema::dropIfExists('competition_requirements');
        Schema::dropIfExists('competitions');
    }
};
