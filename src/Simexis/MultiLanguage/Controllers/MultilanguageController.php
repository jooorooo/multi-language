<?php

namespace Simexis\MultiLanguage\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Simexis\MultiLanguage\Manager;
use Simexis\MultiLanguage\Request\LanguageRequest;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;
use Simexis\MultiLanguage\Commands\FileLoaderCommand;

class MultilanguageController extends Controller {
	
	protected $protected_language = 'en';
	
    /** @var \Simexis\MultiLanguage\Manager */
    protected $manager;

    public function __construct(Manager $manager)
    {
		$this->manager = $manager;
		$this->protected_language = config('multilanguage.locale');
    }

    public function getIndex()
    {	
        $locales = $this->manager->getProviderModel()->get()->map(function($item) {
			$item->progress = $this->manager->getProgress($item->{config('multilanguage.locale_key')});
			return $item;
		});
		
		return view('multilanguage::index')
			->with('locales', $locales)
			->with('protected', $this->protected_language);
    }
	
	public function getLanguageCreate() {
		
		return view('multilanguage::edit')
			->with('locale', null)
			->with('protected', null);
	}
	
	public function postLanguageCreate(LanguageRequest $request) {
		try {
			$this->manager->getProviderModel()->create([
				config('multilanguage.locale_key') => trim(strtolower($request->get(config('multilanguage.locale_key')))),
				'name' => $request->get('name')
			]);
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.success.language_successfully_created'), 'type' => 'success' ]);
		} catch(Exception $e) {
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getLanguageCreate')
				->with([ 'message' => $e->getMessage(), 'type' => 'danger' ])
				->withInput();
		}
	}
	
	public function getLanguageEdit($locale) {
		$locale = $this->manager->findByLocale($locale);
		if(!$locale)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.language_not_found'), 'type' => 'danger' ]);

		return view('multilanguage::edit')
			->with('locale', $locale)
			->with('protected', $this->protected_language);
		
	}
	
	public function postLanguageEdit(LanguageRequest $request, $locale) {
		$locale = $this->manager->findByLocale($locale);
		if(!$locale)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.language_not_found'), 'type' => 'danger' ]);
		
		try {
			$locale->update([
				config('multilanguage.locale_key') => $locale->{config('multilanguage.locale_key')} == $this->protected_language ? $this->protected_language : trim(strtolower($request->get(config('multilanguage.locale_key')))),
				'name' => $request->get('name')
			]);
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.success.language_successfully_edited'), 'type' => 'success' ]);
		} catch(Exception $e) {
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getLanguageEdit', [$locale->{config('multilanguage.locale_key')}])
				->with([ 'message' => $e->getMessage(), 'type' => 'danger' ])
				->withInput();
		}		
		
	}
	
	public function getLanguageDelete($locale) {
		$locale = $this->manager->findByLocale($locale);
		if(!$locale)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.language_not_found'), 'type' => 'danger' ]);
		if(strtolower($locale->{config('multilanguage.locale_key')}) == $this->protected_language)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.delete_protected'), 'type' => 'danger' ]);
		
		try {
			$locale->delete();
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.success.delete_language_success'), 'type' => 'success' ]);
			
		} catch(Exception $e) {
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => $e->getMessage(), 'type' => 'danger' ]);
		}
		
	}
	
	public function getTranslations($locale) {
		$locale = $this->manager->findByLocale($locale);
		if(!$locale)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.language_not_found'), 'type' => 'danger' ]);
		
        $groups = $this->manager->getGroups(config('multilanguage.exclude_groups'));
		$progress = [];
		foreach($groups AS $group) {
			$progress[$group] = $this->manager->getProgressByGroup($locale->{config('multilanguage.locale_key')}, $group);
		}
		
		return view('multilanguage::groups')
			->with('groups', $groups)
			->with('progress', $progress)
			->with('locale', $locale);
		
	}

    public function getView($locale, $group)
    {	
		$locale = $this->manager->findByLocale($locale);
		if(!$locale)
			return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.language_not_found'), 'type' => 'danger' ]);
				
        $locales = $this->manager->getLocales();
        $groups = $this->manager->getGroups(config('multilanguage.exclude_groups'));
		
		$allTranslations = $this->manager->getItems($group);
		$defaults = $this->manager->loadDefault($group); 
		$numTranslations = $allTranslations->where(config('multilanguage.locale_key'), config('multilanguage.locale'))->count();
		$numChanged = $allTranslations->where(config('multilanguage.locale_key'), $locale->{config('multilanguage.locale_key')})->where('locked', Manager::LOCKED)->count();

        $translations = $this->manager->makeTree($allTranslations);

		return view('multilanguage::group_edit')
			->with('translations', $translations)
			->with('locales', $locales)
			->with('locale', $locale)
			->with('groups', $groups)
			->with('defaults', $defaults)
			->with('group', $group)
			->with('numTranslations', $numTranslations)
			->with('numChanged', $numChanged)
			->with('editUrl', action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postEdit', [$group]));
    }

    public function postEdit(Request $request, $groupnamespace)
    {
		list($namespace, $group) = explode('.', $groupnamespace);
		
		if(is_array(config('multilanguage.exclude')) && array_key_exists($namespace, config('multilanguage.exclude')))
            return ['status' => 'error', 'message' => Lang::get('multilanguage::errors.namespace_is_exclude')];
		
		$groups = $this->manager->getGroups(config('translation.exclude'));
        if(!isset($groups[$groupnamespace]))
            return ['status' => 'error', 'message' => Lang::get('multilanguage::errors.group_not_found')];

        if(is_array(config('translation.exclude.'.$namespace)) && in_array($group, config('translation.exclude.'.$namespace)))
            return ['status' => 'error', 'message' => Lang::get('multilanguage::errors.group_is_exclude')];
		
		$name = $request->get('name');
		$value = $request->get('value');

		list($locale, $item) = explode('|', $name, 2);

        $items = $this->manager->loadDefault($groupnamespace);
        if(!isset($items[$item]))
            return ['status' => 'error', 'message' => Lang::get('multilanguage::errors.item_not_found')];
		
		$translation = $this->manager->firstEntryOrNew([
            'namespace' => $namespace,
			config('multilanguage.locale_key') => $locale,
			'group' => $group,
			'item' => $item,
		]);
		
		$defaults = $this->manager->loadDefault($groupnamespace); 
		
		$translation->text = (string) $value ?: '';
		$translation->locked = isset($defaults[$item]) ? ($translation->text != $defaults[$item] ? Manager::LOCKED : Manager::UNLOCKED) : Manager::LOCKED;
		
		if(!$translation->text) {
			try {
				$translation->delete();
				return ['status' => 'delete'];
			} catch(Exception $e) {
				return ['status' => 'error', 'message' => $e->getMessage()];
			}
		}
		
		try {
			$translation->save();
			return ['status' => 'save', 'locked' => $translation->locked];
		} catch(Exception $e) {
			return ['status' => 'error', 'message' => $e->getMessage()];
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
			case 'clear' === $action:
				$counter = $this->manager->clearTranslations();
			break;
			default:		
				return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
						->with([ 'message' => Lang::get('multilanguage::multilanguage.errors.wrong_action'), 'type' => 'danger' ]);
			break;
		}
		return redirect()->action('\Simexis\MultiLanguage\Controllers\MultilanguageController@getIndex')
				->with([ 'message' => Lang::get('multilanguage::multilanguage.form_actions_messages.' . $action, ['counter' => is_numeric($counter) ? $counter : 0]), 'type' => 'info' ]);
    }
	
}