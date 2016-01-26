<?php

use Illuminate\Database\Migrations\Migration;

class CreateLanguageEntriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('language_entries', function($table){
			$table->engine = 'InnoDB';
			$table->increments('id');
            $table->char(config('multilanguage.locale_key'), 2)->index('idx_locale');
			$table->string('namespace', 150)->index('idx_namespace');
			$table->string('group', 150)->index('idx_group');
			$table->string('item', 150)->index('idx_item');
			$table->text('text');
			$table->boolean('unstable')->default(0);
			$table->boolean('locked')->default(0);
			$table->timestamps();
			$table->foreign(config('multilanguage.locale_key'))
				->references(config('multilanguage.locale_key'))
				->on('languages')->onDelete('cascade')->onUpdate('cascade');
			$table->unique(array(config('multilanguage.locale_key'), 'group', 'item'));
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('language_entries');
	}

}