<?php 

namespace Simexis\MultiLanguage\Loaders;

use Illuminate\Translation\LoaderInterface;
use Simexis\MultiLanguage\Loaders\Loader;
use Simexis\MultiLanguage\Providers\LanguageProvider as LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider as LanguageEntryProvider;

class DatabaseLoader extends Loader implements LoaderInterface {

	/**
	 * Load the messages strictly for the given locale.
	 *
	 * @param  Language  	$language
	 * @param  string  		$group
	 * @param  string  		$namespace
	 * @return array
	 */
	public function loadRawLocale($locale, $group, $namespace = null)
	{
		$langArray 	= array();
		$namespace = $namespace ?: '*';
		$language 	= $this->languageProvider->findByLocale($locale);
		if ($language) {
			if(is_object($entries = $language->entries()->where('group', '=', $group)->get()) && $entries->count()) {
				foreach($entries as $entry) {
					array_set($langArray, $entry->item, $entry->text);
				}
			} else if($locale !== config('multilanguage.locale') && !is_null($language	= $this->languageProvider->findByLocale(config('multilanguage.locale')))) {
				$entries = $language->entries()->where('group', '=', $group)->get();
				foreach($entries as $entry) {
					array_set($langArray, $entry->item, $entry->text);
				}
			}
		}
		return $langArray;
	}
	
}