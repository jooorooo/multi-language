<?php 

namespace Simexis\MultiLanguage;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Simexis\MultiLanguage\Loaders\FileLoader;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;

class Manager {

	const LOCKED = 1;
	const UNLOCKED = 0;

    /** @var \Simexis\MultiLanguage\Providers\LanguageProvider */
    protected $provider;
    /** @var \Simexis\MultiLanguage\Providers\LanguageEntryProvider */
    protected $entry;
    /** @var \Simexis\MultiLanguage\Loaders\FileLoader */
    protected $fileLoader;
	
	private $locales;
	
	private $total_rows;

    public function __construct(LanguageProvider $provider, LanguageEntryProvider $entry, FileLoader $fileLoader)
    {
        $this->provider = $provider;
        $this->entry = $entry;
		$this->fileLoader = $fileLoader;
		foreach(app('translator.loader')->getHints() AS $namespace => $path) {
			$this->fileLoader->addNamespace($namespace, $path);
		}
    }

	public function getProvider() {
		return $this->provider;
	}

	public function getProviderModel() {
		return $this->provider->createModel();
	}

	public function getEntry() {
		return $this->entry;
	}

	public function getEntryModel() {
		return $this->entry->createModel();
	}

    public function getLocales()
    {
		if(!is_null($this->locales))
			return $this->locales;
        //Set the default locale as the first one. 
		if(!$this->checkTablesExists())
			return $this->locales = [app()->getLocale() => app()->getLocale()];
        return $this->locales = $this->getProviderModel()->orderBy(DB::raw('FIELD(' . config('multilanguage.locale_key') . ',"' . config('multilanguage.locale') . '") desc, id'),'asc')->get()->lists('name', config('multilanguage.locale_key'))->all();
    }

	/**
	 * Find the language by ISO.
	 *
	 * @param  string  $locale
	 * @return Eloquent NULL in case no language entry was found.
	 */
	public function findByLocale($locale)
	{
		return $this->getProvider()->findByLocale($locale);
	}
	
	public function getGroups($exclude = []) {
		if(!is_array($exclude))
			$exclude = $exclude ? [$exclude] : [];
		
		$query = $this->getEntryModel()->groupBy(\DB::raw('CONCAT(`namespace`,"_",`group`)'))->orderBy('namespace', 'asc')->orderBy('group', 'asc');
		if(is_array($exclude)) {
			foreach($exclude AS $namespace => $groups) {
				$query->where(function($query) use($namespace, $groups) {
					if($groups && is_array($groups)) {
						$groups = array_map(function($string) { return app('db')->getPdo()->quote($string); }, $groups);
						$query->whereRaw('IF(`namespace` = ' . app('db')->getPdo()->quote($namespace) . ',(`group` NOT IN (' . implode(',',$groups) . ')),1)');
					}
				});
			}
		}
		
		$res = $query->get();
		$return = [];
		foreach($res AS $r) {
			$return[$r->namespace . '.' . $r->group] = $r->namespace . '.' . $r->group;
		}
		return $return;
	}
	
	public function getItems($group) {
		list($namespace, $group) = explode('.',$group);
		return $this->getEntryModel()->where('namespace', $namespace)->where('group', $group)->orderBy('item', 'asc')->get();
	}

    public function makeTree($translations)
    {
        $array = array();
        foreach($translations as $translation){
            array_set($array[$translation->item], $translation->{config('multilanguage.locale_key')}, $translation);
        }
        return $array;
    }
    
	public function firstEntryOrNew($data) {
		return $this->getEntryModel()->firstOrNew($data);
	}
    
	public function getPathsWithHints() { 
		$directories = new Collection(['*' => app()->langPath()]);
		if(is_array($hints = app('translator.loader')->getHints()) && count($hints)) {
			foreach($hints AS $namespace => $path) {
				$directories->put($namespace, $path);
			}
		}
		$directories = $directories->filter(function($directory) {
			return strpos($directory, base_path('vendor')) === false;
		});
		return $directories;
	}

    public function importTranslations($replace = false)
    {
        $counter = 0;
		$locales = $this->getLocales();
		$files = new Filesystem();
		
		$directories = $this->getPathsWithHints();
		
        foreach($directories->all() AS $namespace => $direcory) { 
			if(!is_dir($direcory))
				continue;
			foreach($files->directories($direcory) as $langPath){
				$locale = basename($langPath);

				if(!array_key_exists($locale, $locales))
					continue;
				
				foreach($files->files($langPath) as $file) {

					$group = pathinfo($file, PATHINFO_FILENAME);
					
					if(is_array(config('multilanguage.exclude.' . $namespace)) && in_array($group, config('multilanguage.exclude.' . $namespace)))
						continue;

					$translations = $this->fileLoader->load($locale, $group, $namespace);
					if ($translations && is_array($translations)) {
						foreach(array_dot($translations) as $key => $value){
							$value = (string) $value;
							$translation = $this->firstEntryOrNew([
								'namespace' => $namespace,
								config('multilanguage.locale_key') => $locale,
								'group' => $group,
								'item' => $key,
							]);
		
							$forCount = false;
							// Check if the database is different then the files
							$newStatus = $translation->text === $value ? static::UNLOCKED : static::LOCKED;
							if($newStatus !== (int) $translation->locked){
								$translation->locked = $newStatus;
								$forCount = true;
							}
		
							if($replace)
								$translation->locked = static::UNLOCKED;
		
							// Only replace when empty, or explicitly told so
							if($replace || !$translation->text){
								$translation->text = $value;
								$forCount = true;
							}
		
							$save = $translation->save();
							if($forCount)
								$counter++;
						}
					}
				}
			}
		}
        return $counter;
    }

    public function truncateTranslations()
    {
        $total = $this->getEntryModel()->count();
        $this->getEntryModel()->truncate();
		return $total;
    }

    public function loadDefault($group)
    {
        list($namespace, $group) = explode('.',$group);
        $translations = $this->fileLoader->load(config('multilanguage.locale'), $group, $namespace);
		if ($translations && is_array($translations)) 
			return array_dot($translations);
		return [];
    }

	public function clearTranslations() {
		$files = new Filesystem();
		$arrays = [];
		$directories = $this->getPathsWithHints();
		foreach($directories->all() AS $namespace => $direcory) {
			foreach($files->files($direcory . DIRECTORY_SEPARATOR . config('multilanguage.locale')) as $file) {
				$group = pathinfo($file, PATHINFO_FILENAME);
				$translations = $this->fileLoader->load(config('multilanguage.locale'), $group, $namespace);
				if ($translations && is_array($translations)) {
					$arrays[$namespace][$group] = array_dot($translations);
				}
			}
		} 
		if(!$arrays)
			return $this->truncateTranslations();
		
		$locales = $this->getLocales();
		
		$model = $this->getEntryModel()->whereNotIn('namespace', array_keys($arrays))
					->orWhere(function($query) use($locales) {
						$query->whereNotIn(config('multilanguage.locale_key'), array_keys($locales));
					});
		foreach($arrays AS $namespace => $groups) {
			$model = $model->orWhere(function($query) use($namespace, $groups) {
				$query->where('namespace', $namespace)
					->whereNotIn('group', array_keys($groups));
			});
			foreach($groups AS $group => $items) {
				$model = $model->orWhere(function($query) use($namespace, $group, $items) {
					$query->where('namespace', $namespace)
						->where('group', $group)
						->whereNotIn('item', array_keys($items));
				});
			}
		}
		return $model->delete();
	}

	public function getProgress($locale) {
		if($locale != config('multilanguage.locale')) {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->where('locked', static::LOCKED)->count();
		} else {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->count();
		}
		if(!$total)
			return 0;
		if(is_null($this->total_rows)) {
			$files = new Filesystem();
			$arrays = [];
			$directories = $this->getPathsWithHints();
			foreach($directories->all() AS $namespace => $direcory) {
				foreach($files->files($direcory . DIRECTORY_SEPARATOR . config('multilanguage.locale')) as $file) { 
					$group = pathinfo($file, PATHINFO_FILENAME); 
					$translations = $this->fileLoader->load(config('multilanguage.locale'), $group, $namespace);
					if ($translations && is_array($translations)) {
						$arrays = array_merge($arrays, array_dot($translations, $namespace . '.' .$group . '.'));
					}
				}
			}
			$this->total_rows = count($arrays);
		}
		return round(($total/$this->total_rows)*100, 2);
	}

	public function getProgressByGroup($locale, $group = null) {
        list($namespace, $group) = explode('.',$group);
		if(is_null($namespace))
			$namespace = '*';
		
		if($locale != config('multilanguage.locale')) {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->where('namespace',$namespace)->where('group',$group)->where('locked', static::LOCKED)->count();
		} else {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->where('namespace',$namespace)->where('group',$group)->count();
		}
		if(!$total)
			return 0;
		$translations = $this->fileLoader->load(config('multilanguage.locale'), $group, $namespace);
		$total_rows = count(array_dot($translations, $group));
		if(!$total_rows)
			return 0;
		return round(($total/$total_rows)*100, 2);
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function checkTablesExists()
	{
		static $check;
		if(!is_null($check))
			return $check;
		try {
			$check = Schema::hasTable('languages') && Schema::hasTable('language_entries');
			return $check;
		} catch(\Exception $e) {
			return $check = false;
		}
	}
	
}
