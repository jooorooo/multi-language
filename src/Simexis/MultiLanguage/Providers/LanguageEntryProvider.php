<?php 

namespace Simexis\MultiLanguage\Providers;

use Simexis\MultiLanguage\Models\LanguageEntry;

class LanguageEntryProvider {

	/**
	 * Find the language entry by ID.
	 *
	 * @param  int  $id
	 * @return Eloquent NULL in case no language entry was found.
	 */
	public function findById($id)
	{
		return $this->createModel()->newQuery()->find($id);
	}

	/**
	 * Find the entries with a key that starts with the provided key.
	 *
	 * @param  string  	$key
	 * @return Eloquent List.
	 */
	public function findByKey($language, $key)
	{
		return $this->createModel()->newQuery()->where('key', 'LIKE', "$key%")->get();
	}

	/**
	 * Find all entries for a given language.
	 *
	 * @param  Eloquent  	$language
	 * @return Eloquent
	 */
	public function findByLanguage($name)
	{
		return $this->createModel()->newQuery()->where('name', '=', $name)->first();
	}

	/**
	 * Returns all languages.
	 *
	 * @return array  $languages
	 */
	public function findAll()
	{
		return $this->createModel()->newQuery()->get()->all();
	}

	/**
	 *	Returns a language entry that is untranslated in the specified language.
	 *	@param Simexis\MultiLanguage\Models\Language 				$reference
	 *	@param Simexis\MultiLanguage\Models\Language 				$target
	 *	@return Simexis\MultiLanguage\Models\LanguageEntry
	 */
	public function findUntranslated($reference, $target)
	{
		$model = $this->createModel();
		$key = config('multilanguage.locale_key');
		return $model
			->newQuery()
			->where($key, '=', $reference->{$key})
			->whereNotExists(function($query) use ($model, $reference, $target, $key){
				$table = $model->getTable();
				$query
					->from("$table as e")
					->where($key, '=', $target->{$key})
					->whereRaw("e.group = $table.group")
					->whereRaw("e.item = $table.item")
					;
				})
			->first();
	}

	/**
	 * Creates a language.
	 *
	 * @param  array  $attributes
	 * @return Cartalyst\Sentry\languages\GroupInterface
	 */
	public function create(array $attributes)
	{
		$language = $this->createModel();
		$language->fill($attributes)->save();
		return $language;
	}

	/**
	 *	Loads messages into the database
	 *	@param array 			$lines
	 *	@param Language 	$language
	 *	@param string 		$group
	 *	@param string 		$namespace
	 *	@param boolean 		$isDefault
	 *	@return void
	 */
	public function loadArray(array $lines, $language, $group, $namespace = null, $isDefault = false)
	{
		$key = config('multilanguage.locale_key');
		if (! $namespace) {
			$namespace = '*';
		}
		// Transform the lines into a flat dot array:
		$lines = array_dot($lines);
		$save = $replace = 0;
		foreach ($lines as $item => $text) {
			// Check if the entry exists in the database:
			$entry = $this
				->createModel()
				->newQuery()
	      ->where('group', '=', $group)
	      ->where('item', '=', $item)
	      ->where($key, '=', $language->{$key})
	      ->first();

			// If the entry already exists, we update the text:
			if ($entry) {
				$u = $entry->updateText($text, $isDefault);
				if($u)
					$replace++;
			}
			// The entry doesn't exist:
			else {
				$entry = $this->createModel();
				$entry->group = $group;
				$entry->item = $item;
				$entry->text = $text;
				$s = $language->entries()->save($entry);
				if($s)
					$save++;
			}
		}
		return [$save, $replace];
	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function createModel()
	{
		return new LanguageEntry;
	}

}