<?php 

namespace Simexis\MultiLanguage\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageEntry extends Model {

  /**
   *  Table name in the database.
   *  @var string
   */
	protected $table = 'language_entries';

  /**
   *  List of variables that cannot be mass assigned
   *  @var array
   */
  protected $guarded = array('id');

  /**
   *	Each language entry belongs to a language.
   */
  public function language()
  {
  	return $this->belongsTo('Simexis\MultiLanguage\Models\Language', config('multilanguage.locale_key'), config('multilanguage.locale_key'));
  }

  /**
   *  Return the language entry in the default language that corresponds to this entry.
   *  @param Simexis\MultiLanguage\Models\Language  $defaultLanguage
   *  @return Simexis\MultiLanguage\Models\LanguageEntry
   */
  public function original($defaultLanguage)
  {
    if ($this->exists && $defaultLanguage && $defaultLanguage->exists) {
      return $defaultLanguage->entries()->where('group', '=', $this->group)->where('item', '=', $this->item)->first();
    } else {
      return NULL;
    }
  }

  /**
   *  Update the text. In case the second argument is true, then all translations for this entry will be flagged as unstable.
   *  @param  string   $text
   *  @param  boolean  $isDefault
   *  @return boolean
   */
  public function updateText($text, $isDefault = false, $lock = false, $force = false)
  {
    $saved            = false;

    // If the text is locked, do not allow editing:
    if (!$this->locked || $force) {
      $this->text   = $text;
      $this->locked = $lock;
      $saved        = $this->save();
      if ($saved && $isDefault) {
        $this->flagSiblingsUnstable();
      }
    }
    return $saved;
  }

  /**
   *  Flag all siblings as unstable.
   *
   */
  public function flagSiblingsUnstable()
  {
	  $key = config('multilanguage.locale_key');
    if ($this->id) {
      LanguageEntry::where('group', '=', $this->group)
        ->where('item', '=', $this->item)
        ->where($key, '!=', $this->{$key})
        ->update(array('unstable' => '1'));
    }
  }

  /**
   *  Returns a list of entries that contain a translation for this item in the given language.
   *
   *  @param Simexis\MultiLanguage\Models\Language
   *  @return Simexis\MultiLanguage\Models\LanguageEntry
   */
  public function getSuggestedTranslations($language)
  {
	  $key = config('multilanguage.locale_key');
    $self = $this;
    return $language->entries()
        ->select("{$this->table}.*")
        ->join("{$this->table} as e", function($join) use ($self, $key) {
          $join
            ->on('e.group', '=', "{$self->table}.group")
            ->on('e.item', '=', "{$self->table}.item");
        })
        ->where('e.' . $key, '=', $this->{$key})
        ->where('e.text', '=', "{$this->text}")
        ->groupBy("{$this->table}.text")
        ->get();
  }
}