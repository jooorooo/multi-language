<?php 

namespace Simexis\MultiLanguage\Loaders;

use Illuminate\Foundation\Application;
use Illuminate\Translation\LoaderInterface;
use Illuminate\Translation\FileLoader as LaravelFileLoader;
use Simexis\MultiLanguage\Loaders\Loader;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;

class FileLoader extends Loader implements LoaderInterface {

	/**
	 * The laravel file loader instance.
	 *
	 * @var \Illuminate\Translation\FileLoader
	 */
	protected $laravelFileLoader;

	/**
	 * 	Create a new mixed loader instance.
	 *
	 * 	@param  \Waavi\Lang\Providers\LanguageProvider  			$languageProvider
	 * 	@param 	\Waavi\Lang\Providers\LanguageEntryProvider		$languageEntryProvider
	 *	@param 	\Illuminate\Foundation\Application  					$app
	 */
	public function __construct(LanguageProvider $languageProvider, LanguageEntryProvider $languageEntryProvider, Application $app)
	{
		parent::__construct($languageProvider, $languageEntryProvider, $app);
		$this->laravelFileLoader = new LaravelFileLoader($app['files'], app()->langPath());
	}

	/**
	 * Load the messages strictly for the given locale without checking the cache or in case of a cache miss.
	 *
	 * @param  string  $locale
	 * @param  string  $group
	 * @param  string  $namespace
	 * @return array
	 */
	public function loadRawLocale($locale, $group, $namespace = null)
	{
		$namespace = $namespace ?: '*';
		return $this->laravelFileLoader->load($locale, $group, $namespace);
	}

}