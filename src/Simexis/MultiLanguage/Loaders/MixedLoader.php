<?php 

namespace Simexis\MultiLanguage\Loaders;

use Illuminate\Translation\LoaderInterface;
use Simexis\MultiLanguage\Loaders\Loader;
use Simexis\MultiLanguage\Providers\LanguageProvider as LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider as LanguageEntryProvider;

class MixedLoader extends Loader implements LoaderInterface {

	/**
	 *	The file loader.
	 *	@var \Simexis\MultiLanguage\Loaders\FileLoader
	 */
	protected $fileLoader;

	/**
	 *	The database loader.
	 *	@var \Simexis\MultiLanguage\Loaders\DatabaseLoader
	 */
	protected $databaseLoader;

	/**
	 * 	Create a new mixed loader instance.
	 *
	 * 	@param  \Waavi\Lang\Providers\LanguageProvider  			$languageProvider
	 * 	@param 	\Waavi\Lang\Providers\LanguageEntryProvider		$languageEntryProvider
	 *	@param 	\Illuminate\Foundation\Application  					$app
	 */
	public function __construct($languageProvider, $languageEntryProvider, $app)
	{
		parent::__construct($languageProvider, $languageEntryProvider, $app);
		$this->fileLoader 		= new FileLoader($languageProvider, $languageEntryProvider, $app);
		$this->databaseLoader = new DatabaseLoader($languageProvider, $languageEntryProvider, $app);
	}

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
		$namespace = $namespace ?: '*';
		return $this->array_merge($this->fileLoader->loadRawLocale($locale, $group, $namespace), $this->databaseLoader->loadRawLocale($locale, $group, $namespace));
	}
	
	/**
	 * Merge one or more arrays
	 * 
	 * @param
	 *        	array1 array <p>
	 *        	Initial array to merge.
	 *        	</p>
	 * @param
	 *        	array2 array[optional]
	 * @param
	 *        	_ array[optional]
	 * @return array the resulting array.
	 */
	public function array_merge(array $array1, array $array2 = null) {
		$args = func_get_args ();
		if (count ( $args ) < 2)
			return $array1;
		
		for($i = 1; $i < count ( $args ); $i ++) {
			if (is_array ( $args [$i] )) {
				foreach ( $args [$i] as $key => $val ) {
					if (is_array ( $args [$i] [$key] )) {
						$array1 [$key] = (array_key_exists ( $key, $array1 ) && is_array ( $array1 [$key] )) ? $this->array_merge ( $array1 [$key], $args [$i] [$key] ) : $args [$i] [$key];
					} else {
						$array1 [$key] = $val;
					}
				}
			}
		}
		return $array1;
	}
}