Laravel 5.x Multilanguage and localization module
====================

Note: This package use by default use language ISO 639-1 two-letter codes for locale: `https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes`

The idea for this package is based on:
[Laravel-Translatable](https://github.com/dimsav/laravel-translatable)
[Upgrading Laravel's localization module](https://github.com/Waavi/translation)
[Laravel 5 Translation Manager](https://github.com/barryvdh/laravel-translation-manager)

- This is a Laravel package for translatable models. 
- Read all translation files and save them in the database.
- Url modifier for local prefix. Examples: login/ => /it/login . / => /it

## Installation

Require this package in your composer.json and run composer update (or run `composer require simexis/multi-language` directly):

    simexis/multi-language

After updating composer, add the ServiceProvider to the providers array in config/app.php

```php
'providers' => [
    Simexis\MultiLanguage\MultiLanguageServiceProvider::class,
]
```

You need to run the publish and migration.

    $ php artisan vendor:publish
    $ php artisan migrate

Routes are added in the ServiceProvider. You can set the group parameters for the routes in the configuration.
You can change the prefix or filter/middleware for the routes. If you want full customisation, you can extend the ServiceProvider and override the `map()` function.

This example will make the translation manager available at `http://yourdomain.com/multilanguage`

## Usage

### Set current locale.
```php
App::setLocale('ru');
```
Thene package work with `ru` locale.

## Usage translation part

### Web interface

When you have imported your translation (via buttons or command), you can view them in the webinterface (on the url you defined the with the controller).
You can click on a translation and an edit field will popup. Just click save and it is saved :)
When a translation is not yet created in a different locale, you can also just edit it to create it.

Using the buttons on the webinterface, you can append, replace, truncate and clear the translations.

You can also use the commands below.

### Import command

The import command will search through resources/lang and load all strings in the database, so you can easily manage them.

    $ php artisan translator:append
    
Note: By default, only new strings are added. Translations already in the DB are kept the same. If you want to replace all values with the ones from the files, 
use replace command: `php artisan translator:replace`

### Truncate (delete) all transaltions from Database.

    $ php artisan translator:truncate
	
### Clear non existings translations.

    $ php artisan translator:clear

## Usage Url modifier for local prefix

For transform link from `http://yourdomain.com/page` to `http://yourdomain.com/en/page`, just use route or url functions. Example url('page') display `http://yourdomain.com/en/page` where `en` is App::getLocale()
	

## Usage translatable models

## Demo

**Getting translated attributes**

```php
  $greece = Country::where('code', 'gr')->first();
  echo $greece->translate('en')->name; // Greece
  
  App::setLocale('en');
  echo $greece->name;     // Greece

  App::setLocale('de');
  echo $greece->name;     // Griechenland
```

**Saving translated attributes**

```php
  $greece = Country::where('code', 'gr')->first();
  echo $greece->translate('en')->name; // Greece
  
  $greece->translate('en')->name = 'abc';
  $greece->save();
  
  $greece = Country::where('code', 'gr')->first();
  echo $greece->translate('en')->name; // abc
```

**Filling multiple translations**

```php
  $data = [
    'code' => 'gr',
    'en'  => ['name' => 'Greece'],
    'fr'  => ['name' => 'Grece'],
  ];

  $greece = Country::create($data);
  
  echo $greece->translate('fr')->name; // Grece
```

### Step 1: Migrations

In this example, we want to translate the model `Country`. We will need an extra table `country_translations`:

```php
Schema::create('countries', function (Blueprint $table) {
	$table->increments('id');
	$table->string('code');
	$table->timestamps();
});
Schema::create('country_translations', function (Blueprint $table) {
	$table->increments('id');
	$table->integer('country_id')->unsigned();
	$table->string('name');
	$table->char(config('multilanguage.locale_key'), 2)->index('idx_locale');

	$table->unique(['country_id',config('multilanguage.locale_key')]);
	$table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
	$table->foreign(config('multilanguage.locale_key'))
		->references(config('multilanguage.locale_key'))
		->on('languages')->onDelete('cascade')->onUpdate('cascade');
});
```

### Step 2: Models

1. The translatable model `Country` should [use the trait](http://www.sitepoint.com/using-traits-in-php-5-4/) `Simexis\MultiLanguage\Traits\Translatable`. 
2. The convention for the translation model is `CountryTranslation`.

```php
// models/Country.php
class Country extends Model
{

    use \Simexis\MultiLanguage\Traits\Translatable;
    
    public $translatedAttributes = ['name'];
    protected $fillable = ['code', 'name'];
	
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'countries';
}

// models/CountryTranslation.php
use Illuminate\Database\Eloquent\Model;

class CountryTranslation extends Model
{

    public $timestamps = false;
    protected $fillable = ['name'];
	
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'country_translations';
}
```

The array `$translatedAttributes` contains the names of the fields being translated in the "Translation" model.

## Configuration

### The translation model

The convention used to define the class of the translation model is to append the keyword `Translation`.

So if your model is `\MyApp\Models\Country`, the default translation would be `\MyApp\Models\CountryTranslation`.

To use a custom class as translation model, define the translation class (including the namespace) as parameter. For example:

```php
<?php 

namespace App\Models;

class Country extends Model
{

    use \Simexis\MultiLanguage\Traits\Translatable;
    
    public $translatedAttributes = ['name'];
    protected $fillable = ['code', 'name'];
	
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'countries';

	//convention for the translation model is `CountryTranslation`.
    public $translationModel = 'MyApp\Models\CountryAwesomeTranslation';
}

```