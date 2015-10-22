<?php 

namespace Simexis\MultiLanguage\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;
use Simexis\MultiLanguage\Loaders\FileLoader;

class FileLoaderCommand extends Command {

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $name = 'translator:load';

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Load language files into the database.";

	/**
	*  Create a new mixed loader instance.
	*
	*  @param  \Simexis\MultiLanguage\Providers\LanguageProvider        $languageProvider
	*  @param  \Simexis\MultiLanguage\Providers\LanguageEntryProvider   $languageEntryProvider
	*  @param  \Simexis\MultiLanguage\Loaders\FileLoader            	$fileLoader
	*/
	public function __construct(LanguageProvider $languageProvider, LanguageEntryProvider $languageEntryProvider, FileLoader $fileLoader)
	{
		parent::__construct();
		$this->languageProvider       = $languageProvider;
		$this->languageEntryProvider  = $languageEntryProvider;
		$this->fileLoader             = $fileLoader;
		$this->finder                 = new Filesystem();
		$this->path                   = app()->langPath();
	}

	/**
	* Execute the console command.
	*
	* @return void
	*/
	public function fire()
	{
		$localeDirs = $this->finder->directories($this->path);
		foreach($localeDirs as $localeDir) {
			$locale = str_replace($this->path.DIRECTORY_SEPARATOR, '', $localeDir);
			$language = $this->languageProvider->findByLocale($locale);
			
			if ($language) {
				$langFiles = $this->finder->files($localeDir);
				foreach($langFiles as $langFile) {
					$group = str_replace(array('/', $localeDir.DIRECTORY_SEPARATOR, '.php'), array(DIRECTORY_SEPARATOR,'',''), $langFile);
					$lines = $this->fileLoader->loadRawLocale($locale, $group);
					$this->languageEntryProvider->loadArray($lines, $language, $group, null, $locale == $this->fileLoader->getDefaultLocale());
				}
			}
		}
	}
}