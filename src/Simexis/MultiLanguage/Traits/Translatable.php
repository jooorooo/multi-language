<?php 

namespace Simexis\MultiLanguage\Traits;

use App;
use Simexis\MultiLanguage\Traits\Exception\LocalesNotDefinedException;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;

trait Translatable
{
	
	private $__lacales__;
	
    /**
     * Alias for getTranslation()
     *
     * @param strign|null $locale
     * @param bool $withFallback
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translate($locale = null, $withFallback = false)
    {
        return $this->getTranslation($locale, $withFallback);
    }

    /**
     * Alias for getTranslation()
     *
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translateOrDefault($locale)
    {
        return $this->getTranslation($locale, true);
    }

    /**
     * Alias for getTranslationOrNew()
     *
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function translateOrNew($locale)
    {
        return $this->getTranslationOrNew($locale);
    }

    /**
     * @param string|null $locale
     * @param bool        $withFallback
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function getTranslation($locale = null, $withFallback = null)
    {
        $locale = $locale ?: $this->locale();

        $withFallback = $withFallback === null ? $this->useFallback() : $withFallback;

        if ($this->getTranslationByLocaleKey($locale)) {
            $translation = $this->getTranslationByLocaleKey($locale);
        } elseif ($withFallback
            && $this->getFallbackLocale()
            && $this->getTranslationByLocaleKey($this->getFallbackLocale())
        ) {
            $translation = $this->getTranslationByLocaleKey($this->getFallbackLocale());
        } else {
            $translation = null;
        }

        return $translation;
    }

    /**
     * @param string|null $locale
     *
     * @return bool
     */
    public function hasTranslation($locale = null)
    {
        $locale = $locale ?: $this->locale();

        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $locale) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getTranslationModelName()
    {
        return $this->translationModel ?: $this->getTranslationModelNameDefault();
    }

    /**
     * @return string
     */
    public function getTranslationModelNameDefault()
    {
        return get_class($this).config('multilanguage.translation_suffix', 'Translation');
    }

    /**
     * @return string
     */
    public function getRelationKey()
    {
        return $this->translationForeignKey ?: $this->getForeignKey();
    }

    /**
     * @return string
     */
    public function getLocaleKey()
    {
        return $this->localeKey ?: config('multilanguage.locale_key', 'locale');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translations()
    {
        return $this->hasMany($this->getTranslationModelName(), $this->getRelationKey());
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (str_contains($key, ':')) {
            list($key, $locale) = explode(':', $key);
        } else {
            $locale = $this->locale();
        }

        if ($this->isTranslationAttribute($key)) {
            if ($this->getTranslation($locale) === null) {
                return;
            }

            return $this->getTranslation($locale)->$key;
        }

        return parent::getAttribute($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute($key, $value)
    {
        if (str_contains($key, ':')) {
            list($key, $locale) = explode(':', $key);
        } else {
            $locale = $this->locale();
        }

        if (in_array($key, $this->translatedAttributes)) {
            $this->getTranslationOrNew($locale)->$key = $value;
        } else {
            parent::setAttribute($key, $value);
        }
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            if (count($this->getDirty()) > 0) {
                // If $this->exists and dirty, parent::save() has to return true. If not,
                // an error has occurred. Therefore we shouldn't save the translations.
                if (parent::save($options)) {
                    return $this->saveTranslations();
                }

                return false;
            } else {
                // If $this->exists and not dirty, parent::save() skips saving and returns
                // false. So we have to save the translations
                if ($saved = $this->saveTranslations()) {
                    $this->fireModelEvent('saved', false);
                }

                return $saved;
            }
        } elseif (parent::save($options)) {
            // We save the translations only if the instance is saved in the database.
            return $this->saveTranslations();
        }

        return false;
    }

    /**
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    protected function getTranslationOrNew($locale)
    {
        if (($translation = $this->getTranslation($locale, false)) === null) {
            $translation = $this->getNewTranslation($locale);
        }

        return $translation;
    }

    /**
     * @param array $attributes
     *
     * @return $this
     *
     * @throws \Illuminate\Database\Eloquent\MassAssignmentException
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();

        foreach ($attributes as $key => $values) {
            if ($this->isKeyALocale($key)) {
                foreach ($values as $translationAttribute => $translationValue) {
                    if ($this->alwaysFillable() || $this->isFillable($translationAttribute)) {
                        $this->getTranslationOrNew($key)->$translationAttribute = $translationValue;
                    } elseif ($totallyGuarded) {
                        throw new MassAssignmentException($key);
                    }
                }
                unset($attributes[$key]);
            }
        }

        return parent::fill($attributes);
    }

    /**
     * @param string $key
     */
    private function getTranslationByLocaleKey($key)
    {
        foreach ($this->translations as $translation) {
            if ($translation->getAttribute($this->getLocaleKey()) == $key) {
                return $translation;
            }
        }

        return;
    }

    /**
     * @return string
     */
    private function getFallbackLocale()
    {
        return config('multilanguage.fallback_locale');
    }

    /**
     * @return bool|null
     */
    private function useFallback()
    {
        if (isset($this->useTranslationFallback) && $this->useTranslationFallback !== null) {
            return $this->useTranslationFallback;
        }

        return config('multilanguage.use_fallback');
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function isTranslationAttribute($key)
    {
        return in_array($key, $this->translatedAttributes);
    }

    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws \Simexis\MultiLanguage\Traits\Exception\LocalesNotDefinedException
     */
    protected function isKeyALocale($key)
    {
        $locales = $this->getLocales();

        return in_array($key, $locales);
    }

    /**
     * @return array
     *
     * @throws \Simexis\MultiLanguage\Traits\Exception\LocalesNotDefinedException
     */
    protected function getLocales()
    {
		
		if(is_null($this->__lacales__)) {
			$localesDb = with(new LanguageProvider(config('multilanguage.language.model')))->findAll();
			$this->__lacales__ = [];
			foreach($localesDb AS $l) {
				$this->__lacales__[] = $l->{config('multilanguage.locale_key')};
			}
		}
		
        $locales = $this->__lacales__;

        if (empty($locales)) {
            throw new LocalesNotDefinedException('Please make sure you have run "php artisan vendor:publish simexis/multi-language" and "php artisan migrate"');
        }

        return $locales;
    }

    /**
     * @return bool
     */
    protected function saveTranslations()
    {
        $saved = true;
        foreach ($this->translations as $translation) {
            if ($saved && $this->isTranslationDirty($translation)) {
                $translation->setAttribute($this->getRelationKey(), $this->getKey());
                $saved = $translation->save();
            }
        }

        return $saved;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $translation
     *
     * @return bool
     */
    protected function isTranslationDirty(Model $translation)
    {
        $dirtyAttributes = $translation->getDirty();
        unset($dirtyAttributes[$this->getLocaleKey()]);

        return count($dirtyAttributes) > 0;
    }

    /**
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getNewTranslation($locale)
    {
        $modelName = $this->getTranslationModelName();
        $translation = new $modelName();
        $translation->setAttribute($this->getLocaleKey(), $locale);
        $this->translations->add($translation);

        return $translation;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return (in_array($key, $this->translatedAttributes) || parent::__isset($key));
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeTranslatedIn(Builder $query, $locale = null)
    {
        $locale = $locale ?: $this->locale();

        return $query->whereHas('translations', function (Builder $q) use ($locale) {
            $q->where($this->getLocaleKey(), '=', $locale);
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeTranslated(Builder $query)
    {
        return $query->has('translations');
    }

    /**
     * Adds scope to get a list of translated attributes, using the current locale.
     *
     * Example usage: Country::listsTranslations('name')->get()->toArray()
     * Will return an array with items:
     *  [
     *      'id' => '1',                // The id of country
     *      'name' => 'Griechenland'    // The translated name
     *  ]
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $translationField
     */
    public function scopeListsTranslations(Builder $query, $translationField)
    {
        $withFallback = $this->useFallback();

        $query
            ->select($this->getTable().'.'.$this->getKeyName(), $this->getTranslationsTable().'.'.$translationField)
            ->leftJoin($this->getTranslationsTable(), $this->getTranslationsTable().'.'.$this->getRelationKey(), '=', $this->getTable().'.'.$this->getKeyName())
            ->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->locale())
        ;
        if ($withFallback) {
            $query->orWhere(function (Builder $q) {
                $q->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->getFallbackLocale())
                    ->whereNotIn($this->getTranslationsTable().'.'.$this->getRelationKey(), function (QueryBuilder $q) {
                        $q->select($this->getTranslationsTable().'.'.$this->getRelationKey())
                            ->from($this->getTranslationsTable())
                            ->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->locale());
                    });
            });
        }
    }

    /**
     * This scope eager loads the translations for the default and the fallback locale only.
     * We can use this as a shortcut to improve performance in our application.
     *
     * @param Builder $query
     */
    public function scopeWithTranslation(Builder $query)
    {
        $query->with(['translations' => function($query){
            $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->locale());

            if ($this->useFallback()) {
                return $query->orWhere($this->getTranslationsTable().'.'.$this->getLocaleKey(), $this->getFallbackLocale());
            }
        }]);
    }


    /**
     * This scope filters results by checking the translation fields.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string                                $key
     * @param string                                $value
     * @param string                                $locale
     *
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public function scopeWhereTranslation($query, $key, $value, $locale = null)
    {
        return $query->whereHas('translations', function ($query) use ($key, $value, $locale) {
            $query->where($this->getTranslationsTable().'.'.$key, $value);
            if ($locale) {
                $query->where($this->getTranslationsTable().'.'.$this->getLocaleKey(), $locale);
            }
        });
    }


    /**
     * @return array
     */
    public function toArray()
    {
        $attributes = parent::toArray();

        $hiddenAttributes = $this->getHidden();

        foreach ($this->translatedAttributes as $field) {
            if (in_array($field, $hiddenAttributes)) {
                continue;
            }

            if ($translations = $this->getTranslation()) {
                $attributes[$field] = $translations->$field;
            }
        }

        return $attributes;
    }

    /**
     * @return bool
     */
    private function alwaysFillable()
    {
        return config('multilanguage.always_fillable', false);
    }

    /**
     * @return string
     */
    private function getTranslationsTable()
    {
        return App::make($this->getTranslationModelName())->getTable();
    }

    /**
     * @return string
     */
    protected function locale()
    {
        return config('multilanguage.locale')
            ?: App::make('translator')->getLocale();
    }
}