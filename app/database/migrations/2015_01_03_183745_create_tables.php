<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTables extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function($table)
		{
			$table->increments('id');
			$table->string('name', 20);
			$table->string('hash', 255);
			$table->string('token')->nullable()->default(NULL);
			$table->enum('os', array('android', 'ios', 'windows'));
			$table->unique('name');
		});

		Schema::create('friends', function($table)
		{
			$table->increments('id');
			$table->unsignedInteger('user_1');
			$table->unsignedInteger('user_2');

			$table->unique(array('user_1', 'user_2'));
		});

		Schema::table('friends', function($table)
		{
			$table->foreign('user_1')
				->references('id')->on('users')
				->onDelete('cascade');
			$table->foreign('user_2')
				->references('id')->on('users')
				->onDelete('cascade');
		});

		Schema::create('notification_queue', function($table)
		{
			$table->increments('id');
			$table->unsignedInteger('user_id');
			$table->string('friend_name', 20);
			$table->timestamp('timestamp')->default(DB::raw('CURRENT_TIMESTAMP'));
		});

		Schema::table('notification_queue', function($table)
		{
		    $table->foreign('user_id')
				->references('id')->on('users')
				->onDelete('cascade');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('notification_queue');
		Schema::drop('friends');
		Schema::drop('users');
	}

}
