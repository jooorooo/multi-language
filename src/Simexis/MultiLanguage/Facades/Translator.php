<?php 

namespace Simexis\MultiLanguage\Facades;

use Illuminate\Translation\Translator as LaravelTranslator;

class Translator extends LaravelTranslator {

	/**
	 *	Returns the language provider:
	 *	@return Simexis\MultiLanguage\Providers\LanguageProvider
	 */
	public function getLanguageProvider()
	{
		return $this->loader->getLanguageProvider();
	}

	/**
	 *	Returns the language entry provider:
	 *	@return Simexis\MultiLanguage\Providers\LanguageEntryProvider
	 */
	public function getLanguageEntryProvider()
	{
		return $this->loader->getLanguageEntryProvider();
	}

}