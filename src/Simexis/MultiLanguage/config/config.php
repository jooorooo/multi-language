<?php

return array(

	/*
	|--------------------------------------------------------------------------
	| Default Translation Mode
	|--------------------------------------------------------------------------
	|
	| This option controls the translation's bundle mode of operation.
	|
	| Supported:
	| 	'auto'				Uses laravel's 'debug' configuration value to determine which mode operation to choose.
	| 								If debug is true, then the 'mixed' mode is selected.
	|									If debug is false, then the 'database' mode is selected.
	| 	'mixed'				Both files and the database are queried for language entries, with files taking priority.
	| 	'database' 		Use the database as the exclusive source for language entries.
	|   'filesystem'	Use files as the exclusive source for language entries [Laravel's default].
	*/
	'mode'					=>	'auto',

	/*
	|--------------------------------------------------------------------------
	| Default Translation Cache
	|--------------------------------------------------------------------------
	|
	| Choose whether to leverage Laravel's cache module and how to do so.
	|
	| Supported:
	| 	enabled:	'auto'	Uses laravel's 'debug' configuration value to determine whether to activate the cache or not.
	| 										If debug is true, then the cache is deactivated.
	| 										If debug is false, then the cache is active.
	| 						'on'		Use Laravel's cache for language entries.
	| 						'off'		Do not use Laravel's cache for language entries.
	|
	*/
	'cache'					=>	array(
		'enabled' 	=>	'auto',
		'timeout'		=>	60,					// minutes
	),

    /*
    |--------------------------------------------------------------------------
    | Locale key
    |--------------------------------------------------------------------------
    |
    | Defines the 'locale' field name, which is used by the
    | translation model.
    |
    */
    'locale_key' => 'locale',

    /*
    |--------------------------------------------------------------------------
    | Translation Suffix
    |--------------------------------------------------------------------------
    |
    | Defines the default 'Translation' class suffix. For example, if
    | you want to use CountryTrans instead of CountryTranslation
    | application, set this to 'Trans'.
    |
    */
    'translation_suffix' => 'Translation',

    /*
    |--------------------------------------------------------------------------
    | Make translated attributes always fillable
    |--------------------------------------------------------------------------
    |
    | If true, translatable automatically sets
    | translated attributes as fillable.
    |
    | WARNING!
    | Set this to true only if you understand the security risks.
    |
    */
    'always_fillable' => false,

    /*
    |--------------------------------------------------------------------------
    | Default locale
    |--------------------------------------------------------------------------
    |
    | As a default locale, Translatable takes the locale of Laravel's
    | translator. If for some reason you want to override this,
    | you can specify what default should be used here.
    |
    */
    'locale' => config('app.locale'),

    /*
    |--------------------------------------------------------------------------
    | Use fallback
    |--------------------------------------------------------------------------
    |
    | Determine if fallback locales are returned by default or not. To add
    | more flexibility and configure this option per "translatable"
    | instance, this value will be overridden by the property
    | $useTranslationFallback when defined
    |
    */
    'use_fallback' => false,

    /*
    |--------------------------------------------------------------------------
    | Fallback Locale
    |--------------------------------------------------------------------------
    |
    | A fallback locale is the locale being used to return a translation
    | when the requested translation is not existing. To disable it
    | set it to false.
    |
    */
    'fallback_locale' => config('app.fallback_locale'),

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    | The default group settings for the elFinder routes.
    |
    */
    'route' => [
        'prefix' => 'multilanguage',
        'middleware' => 'auth',
    ],

	/**
	 * Exclude specific groups from Laravel Translation Manager. 
	 * This is useful if, for example, you want to avoid editing the official Laravel language files.
	 *
	 * @type array
	 *
	 * 	array(
	 *		'pagination',
	 *		'reminders',
	 *		'validation',
	 *	)
	 */
	'exclude_groups' => array(),
	
);