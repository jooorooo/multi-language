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

	

}
