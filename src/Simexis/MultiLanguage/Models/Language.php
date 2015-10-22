<?php 

namespace Simexis\MultiLanguage\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model {

  /**
   *  Table name in the database.
   *  @var string
   */
	protected $table = 'languages';

  /**
   *  Allow for languages soft delete.
   *  @var boolean
   */
  protected $softDelete = true;

  /**
   *  List of variables that cannot be mass assigned
   *  @var array
   */
  protected $guarded = array('id');

  /**
   *	Each language may have several entries.
   */
  public function entries()
  {
  	return $this->hasMany('Simexis\MultiLanguage\Models\LanguageEntry', config('multilanguage.locale_key'), config('multilanguage.locale_key'));
  }

}