<?php

namespace Simexis\MultiLanguage\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;

class MultilanguageController extends Controller {
	
    /** @var \Simexis\MultiLanguage\Providers\LanguageProvider  */
    protected $provider;
	
    /** @var \Simexis\MultiLanguage\Providers\LanguageEntryProvider  */
    protected $entry;

    public function __construct(LanguageProvider $provider, LanguageEntryProvider $entry)
    {
        $this->provider = $provider;
		$this->entry = $entry;
    }

    public function getIndex($group = null)
    {
        $locales = $this->loadLocales();
        $groups = $this->entry->createModel()->groupBy('group');
        $excludedGroups = config('multilanguage.exclude_groups');
        if($excludedGroups){
            $groups->whereNotIn('group', $excludedGroups);
        }

        $groups = $groups->lists('group', 'group');
        if ($groups instanceof Collection) {
            $groups = $groups->all();
        }
        $groups = [''=>'Choose a group'] + $groups;
        $numChanged = $this->entry->createModel()->where('group', $group)->count();


        $allTranslations = $this->entry->createModel()->where('group', $group)->orderBy('item', 'asc')->get();
        $numTranslations = count($allTranslations);
        $translations = [];
        foreach($allTranslations as $translation){
            $translations[$translation->namespace.'|'.$translation->group.'|'.$translation->item][$translation->{config('multilanguage.locale_key')}] = $translation;
        }

         return view('multilanguage::index')
            ->with('translations', $translations)
            ->with('locales', $locales)
            ->with('groups', $groups)
            ->with('group', $group)
            ->with('numTranslations', $numTranslations)
            ->with('numChanged', $numChanged)
            ->with('editUrl', action('\Simexis\MultiLanguage\Controllers\MultilanguageController@postEdit', [$group]))
            ->with('deleteEnabled', config('multilanguage.delete_enabled'));
    }

    public function getView($group)
    {
        return $this->getIndex($group);
    }

    public function postAdd(Request $request, $group)
    {
        $keys = explode("\n", $request->get('keys'));

        foreach($keys as $key){
            $key = trim($key);
            if($group && $key){
                $this->manager->missingKey('*', $group, $key);
            }
        }
        return redirect()->back();
    }

    public function postEdit(Request $request, $group)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups'))) {
            $name = $request->get('name');
            $value = $request->get('value');

            list($locale, $key) = explode('|', $name, 2);
            $translation = Translation::firstOrNew([
                'locale' => $locale,
                'group' => $group,
                'key' => $key,
            ]);
            $translation->value = (string) $value ?: null;
            $translation->status = Translation::STATUS_CHANGED;
            $translation->save();
            return array('status' => 'ok');
        }
    }

    public function postDelete($group, $key)
    {
        if(!in_array($group, $this->manager->getConfig('exclude_groups')) && $this->manager->getConfig('delete_enabled')) {
            Translation::where('group', $group)->where('key', $key)->delete();
            return ['status' => 'ok'];
        }
    }

    public function postImport(Request $request)
    {
        $replace = $request->get('replace', false);
        $counter = $this->manager->importTranslations($replace);

        return ['status' => 'ok', 'counter' => $counter];
    }
    
    public function postFind()
    {
        $numFound = $this->manager->findTranslations();

        return ['status' => 'ok', 'counter' => (int) $numFound];
    }

    public function postPublish($group)
    {
        $this->manager->exportTranslations($group);

        return ['status' => 'ok'];
    }

    protected function loadLocales()
    {
        //Set the default locale as the first one.
        $locales = $this->entry->createModel()->groupBy(config('multilanguage.locale_key'))->lists(config('multilanguage.locale_key'));
        if ($locales instanceof Collection) {
            $locales = $locales->all();
        } 
        $locales = array_merge([config('app.locale')], $locales);
        return array_unique($locales);
    }
	
}