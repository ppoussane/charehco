<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional Farsi versions of content. When a Farsi field is empty,
 * FA pages fall back to the English text.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('title_fa')->nullable()->after('title');
            $table->string('summary_fa', 500)->nullable()->after('summary');
            $table->text('body_fa')->nullable()->after('body');
            $table->string('location_fa')->nullable()->after('location');
            $table->string('category_fa')->nullable()->after('category');
        });

        Schema::table('competitions', function (Blueprint $table) {
            $table->string('title_fa')->nullable()->after('title');
            $table->string('summary_fa', 500)->nullable()->after('summary');
            $table->text('description_fa')->nullable()->after('description');
            $table->string('location_fa')->nullable()->after('location');
            $table->string('prize_fa')->nullable()->after('prize');
        });

        Schema::table('competition_requirements', function (Blueprint $table) {
            $table->string('label_fa')->nullable()->after('label');
            $table->string('description_fa', 500)->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn(['title_fa', 'summary_fa', 'body_fa', 'location_fa', 'category_fa']));
        Schema::table('competitions', fn (Blueprint $table) => $table->dropColumn(['title_fa', 'summary_fa', 'description_fa', 'location_fa', 'prize_fa']));
        Schema::table('competition_requirements', fn (Blueprint $table) => $table->dropColumn(['label_fa', 'description_fa']));
    }
};
