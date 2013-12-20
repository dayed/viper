<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGamesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up() {
		Schema::create('games', function(Blueprint $table) {
			$table->increments('id');
			$table->string('name', 100);
			$table->string('model', 100);
			$table->text('description');
			$table->string('key', 32);
			$table->string('secret', 64);
			$table->boolean('active');
			$table->timestamps();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down() {
		Schema::drop('games');
	}

}