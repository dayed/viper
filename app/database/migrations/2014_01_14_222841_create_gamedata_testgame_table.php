<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamedataTestgameTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
        Schema::create('gamedata_testgame', function(Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->integer('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->integer('score')->default(0);
            $table->timestamps();
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
        Schema::drop('gamedata_testgame');
	}

}