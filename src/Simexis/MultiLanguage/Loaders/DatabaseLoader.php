<?php 

namespace Simexis\MultiLanguage\Loaders;

use Illuminate\Translation\LoaderInterface;
use Simexis\MultiLanguage\Loaders\Loader;
use Simexis\MultiLanguage\Providers\LanguageProvider as LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider as LanguageEntryProvider;

class DatabaseLoader extends Loader implements LoaderInterface {

	private $language = [];
	private $entries = [];
	
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
		$language 	= $this->getLanguage($locale);
		if ($language) {
			if(is_object($entries = $this->getLanguageEntry($locale, $group)) && $entries->count()) { 
				foreach($entries as $entry) {
					array_set($langArray, $entry->item, $entry->text);
				}
			} else if($locale !== $this->getDefaultLocale() && !is_null($language = $this->getLanguage($this->getDefaultLocale()))) {
				$entries = $this->getLanguageEntry($this->getDefaultLocale(), $group);
				foreach($entries as $entry) {
					array_set($langArray, $entry->item, $entry->text);
				}
			}
		}
		return $langArray;
	}

    protected function getLanguage($locale) {
        if(!array_key_exists($locale, $this->language))
            $this->language[$locale] = $this->languageProvider->findByLocale($locale);
        return $this->language[$locale];
    }

    protected function getLanguageEntry($locale, $group) {
        if(!isset( $this->entries[$locale][$group] ))
            $this->entries[$locale][$group] = $this->getLanguage($locale)
                ->entries()->whereGroup($group)->get();
        return $this->entries[$locale][$group];
    }
	
}