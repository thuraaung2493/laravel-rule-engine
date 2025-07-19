<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_group_id')->index()->constrained('rule_groups')->onDelete('cascade');
            $table->string('name');
            $table->text('expression');
            $table->integer('priority')->default(0)->index();
            $table->string('action_type')->nullable();
            $table->json('action_value')->nullable();
            $table->string('error_message')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rules');
    }
};
