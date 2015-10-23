<?php 

namespace Simexis\MultiLanguage;

use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
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
		if(is_array(!$exclude))
			$exclude = [$exclude];
		return $this->getEntryModel()->groupBy('group')->whereNotIn('group', $exclude)->orderBy('group', 'asc')->lists('group', 'group')->all();
	}
	
	public function getItems($group) {
		return $this->getEntryModel()->where('group', $group)->orderBy('item', 'asc')->get();
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

    public function importTranslations($replace = false)
    {
        $counter = 0;
		$locales = $this->getLocales();
		$files = new Filesystem();
        foreach($files->directories(app()->langPath()) as $langPath){
            $locale = basename($langPath);

			if(!array_key_exists($locale, $locales))
				continue;
			
            foreach($files->files($langPath) as $file) {

                $group = pathinfo($file, PATHINFO_FILENAME);
				
                if(is_array(config('multilanguage.exclude_groups')) && in_array($group, config('multilanguage.exclude_groups')))
                    continue;

                $translations = $this->fileLoader->load($locale, $group);
                if ($translations && is_array($translations)) {
                    foreach(array_dot($translations) as $key => $value){
						$value = (string) $value;
						$translation = $this->firstEntryOrNew([
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
        return $counter;
    }

    public function truncateTranslations()
    {
        return $this->getEntryModel()->truncate();
    }

    public function loadDefault($group)
    {
        $translations = $this->fileLoader->load(config('multilanguage.locale'), $group);
		if ($translations && is_array($translations)) 
			return array_dot($translations);
		return [];
    }

	public function clearTranslations() {
		$files = new Filesystem();
		$arrays = [];
		foreach($files->files(app()->langPath() . DIRECTORY_SEPARATOR . config('multilanguage.locale')) as $file) {
			$group = pathinfo($file, PATHINFO_FILENAME);
			$translations = $this->fileLoader->load(config('multilanguage.local'), $group);
			if ($translations && is_array($translations)) {
				$arrays[$group] = array_dot($translations);
			}
		}
		if(!$arrays)
			return $this->truncateTranslations();
		
		$model = $this->getEntryModel()->whereNotIn('group', array_keys($arrays));
		foreach($arrays AS $group => $items) {
			$model = $model->orWhere(function($query) use($group, $items) {
				$query->where('group', $group)
					->whereNotIn('item', array_keys($items));
			});
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
			foreach($files->files(app()->langPath() . DIRECTORY_SEPARATOR . config('multilanguage.locale')) as $file) {
				$group = pathinfo($file, PATHINFO_FILENAME);
				$translations = $this->fileLoader->load(config('multilanguage.local'), $group);
				if ($translations && is_array($translations)) {
					$arrays = array_merge($arrays, array_dot($translations, $group));
				}
			}
			$this->total_rows = count($arrays);
		}
		return round(($total/$this->total_rows)*100, 2);
	}

	public function getProgressByGroup($locale, $group = null) {
		if($locale != config('multilanguage.locale')) {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->where('group',$group)->where('locked', static::LOCKED)->count();
		} else {
			$total = $this->getEntryModel()->where(config('multilanguage.locale_key'),$locale)->where('group',$group)->count();
		}
		if(!$total)
			return 0;
		$translations = $this->fileLoader->load(config('multilanguage.local'), $group);
		$total_rows = count(array_dot($translations, $group));
		if(!$total_rows)
			return 0;
		return round(($total/$total_rows)*100, 2);
	}
	
}
