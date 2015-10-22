<?php

namespace Simexis\MultiLanguage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Simexis\MultiLanguage\Manager;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;
use Simexis\MultiLanguage\Commands\FileLoaderCommand;

class MultilanguageController extends Controller {
	
    /** @var \Simexis\MultiLanguage\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
		$this->manager = $manager;
    }

    public function getIndex($group = null)
    {
        $locales = $this->manager->getLocales();
        $groups = $this->manager->getGroups(config('multilanguage.exclude_groups'));
        $groups = [''=>'Choose a group'] + $groups;
        
		$allTranslations = $this->manager->getItems($group);
		$numTranslations = $allTranslations->count();
		$numChanged = $allTranslations->where('locked', Manager::LOCKED)->count();

        $translations = $this->manager->makeTree($allTranslations);

		return view('multilanguage::index')
			->with('translations', $translations)
			->with('locales', $locales)
			->with('groups', $groups)
			->with('group', $group)
			->with('numTranslations', $numTranslations)
			->with('numChanged', $numChanged)
			->with('editUrl', action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postEdit', [$group]));
    }

    public function getView($group)
    {
        return $this->getIndex($group);
    }

    public function postEdit(Request $request, $group)
    {
        if(!in_array($group, (array)config('multilanguage.exclude_groups'))) {
            $name = $request->get('name');
            $value = $request->get('value');

            list($locale, $item) = explode('|', $name, 2);
			
			$translation = $this->manager->firstEntryOrNew([
                config('multilanguage.locale_key') => $locale,
                'group' => $group,
                'item' => $item,
            ]);
			
            $translation->text = (string) $value ?: '';
            $translation->locked = Manager::LOCKED;
            return $translation->save() ? ['status' => 'ok'] : ['status' => 'error'];
        }
    }

    public function postImport(Request $request)
    {
		$action = strtolower($request->get('replace', 'append'));
		$counter = 0;
		switch(true) {
			case 'append' === $action:
			case 'replace' === $action:
				$counter = $this->manager->importTranslations('append' !== $action);
			break;
			case 'truncate' === $action:
				$counter = $this->manager->truncateTranslations();
			break;
		}
		

        return ['status' => 'ok', 'counter' => $counter, 'action' => $action];
    }
	
}