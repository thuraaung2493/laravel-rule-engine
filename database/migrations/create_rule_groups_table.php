<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Thuraaung\RuleEngine\Enums\EvaluationLogic;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rule_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->integer('priority')->default(0)->index();
            $table->enum('evaluation_logic', EvaluationLogic::values())->default(EvaluationLogic::ANY);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rule_groups');
    }
};
