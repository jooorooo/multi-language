<?php

use Illuminate\Database\Migrations\Migration;

//https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes

class CreateLanguagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('languages', function($table){
			$table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
			$table->increments('id');
            $table->string('name');
            $table->char(config('multilanguage.locale_key'), 2)->index('idx_locale');
            $table->timestamps();
			
			$table->softDeletes();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('languages');
	}

}