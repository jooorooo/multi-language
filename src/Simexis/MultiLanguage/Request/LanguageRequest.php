<?php 

namespace Simexis\MultiLanguage\Request;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Input;
use Illuminate\Foundation\Http\FormRequest;
use Simexis\MultiLanguage\Manager;

class LanguageRequest extends FormRequest {

	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize()
	{
        return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(Manager $manager)
	{
		$locale = $manager->findByLocale(Input::get(config('multilanguage.locale_key')));
		return [
			'name' => 'required',
			config('multilanguage.locale_key') => 'required|alpha|size:2|unique:languages,' . config('multilanguage.locale_key') . ',' . ($locale?$locale->id:0)
		];
	}

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
		return [
			'name' => Lang::get('multilanguage::multilanguage.entries.name'),
			config('multilanguage.locale_key') => Lang::get('multilanguage::multilanguage.entries.locale'),
		];
    }

}
