<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('results', function (Blueprint $table) {
            $table->uuid('id')->unique()->primary();
            $table->uuid('home_team_id')->references('id')->on('teams');
            $table->uuid('away_team_id')->references('id')->on('teams');
            $table->tinyInteger('home_goals')->nullable();
            $table->tinyInteger('away_goals')->nullable();
            $table->tinyInteger('round');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('results', function (Blueprint $table) {
            $table->dropIndex(['id']);
            $table->dropForeign(['home_team_id']);
            $table->dropForeign(['away_team_id']);
        });
        Schema::dropIfExists('results');
    }
};
