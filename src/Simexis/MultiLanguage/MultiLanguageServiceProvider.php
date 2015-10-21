<?php 

namespace Simexis\MultiLanguage;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use Illuminate\Translation\TranslationServiceProvider;
use Simexis\MultiLanguage\Facades\Translator;
use Simexis\MultiLanguage\Loaders\FileLoader;
use Simexis\MultiLanguage\Loaders\DatabaseLoader;
use Simexis\MultiLanguage\Loaders\MixedLoader;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;

class MultiLanguageServiceProvider extends TranslationServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
	 * Register the service provider.
	 *
	 * @return void
	 */
    public function register()
    { 
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('multilanguage.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__.'/config/config.php', 'multilanguage'
        );
		
		$this->registerLoader();
		$this->registerTranslationFileLoader();

		$this->commands('translator.load');
		
		$this->registerTranslator();
		
    }

	/**
	 * Register the translation line loader.
	 *
	 * @return void
	 */
	protected function registerLoader()
	{ 
		$this->app->bindShared('translation.loader', function($app)
		{ 
			$languageProvider 	= new LanguageProvider($app['config']->get('multilanguage.language.model'));
			$langEntryProvider 	= new LanguageEntryProvider($app['config']->get('multilanguage.language_entry.model'));

			$mode = $app['config']->get('multilanguage.mode');

			if ($mode == 'auto' || empty($mode))
				$mode = ($this->app['config']->get('app.debug') ? 'mixed' : 'database');
			
			if(!$this->checkTablesExists())
				$mode = 'filesystem';
			
			$this->app->bindShared('translation.provider', function($app) use($languageProvider){
				return $languageProvider;
			});
			
			$this->app->bindShared('translation.provider.entry', function($app) use($langEntryProvider){
				return $langEntryProvider;
			});
			
			switch ($mode) {
				case 'mixed':
					return new MixedLoader($languageProvider, $langEntryProvider, $app);

				default: 
				case 'filesystem':
					return new FileLoader($languageProvider, $langEntryProvider, $app);

				case 'database':
					return new DatabaseLoader($languageProvider, $langEntryProvider, $app);
			}
		});
	}

	/**
	 * Register the translation file loader command.
	 *
	 * @return void
	 */
	public function registerTranslationFileLoader()
	{
		$this->app->bindShared('translator.load', function($app)
		{
			$languageProvider 	= new LanguageProvider($app['config']->get('multilanguage.language.model'));
			$langEntryProvider 	= new LanguageEntryProvider($app['config']->get('multilanguage.language_entry.model'));
			$fileLoader 				= new FileLoader($languageProvider, $langEntryProvider, $app);
			return new Commands\FileLoaderCommand($languageProvider, $langEntryProvider, $fileLoader);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function registerTranslator()
	{
		$this->app->bindShared('translator', function($app)
		{
			$loader = $app['translation.loader'];

			// When registering the translator component, we'll need to set the default
			// locale as well as the fallback locale. So, we'll grab the application
			// configuration so we can easily get both of these values from there.
			$locale = $app['config']->get('app.locale');

			$trans = new Translator($loader, $locale);

			return $trans;
		});
	}

	/**
	 * Bootstrap the application events.
	 *
     * @param  \Illuminate\Routing\Router  $router
	 * @return void
	 */
    public function boot(Router $router)
    { 
        $viewPath = __DIR__.'/resources/views';
        $this->loadViewsFrom($viewPath, 'multilanguage');
        $this->publishes([
            $viewPath => base_path('resources/views/vendor/multilanguage'),
        ], 'views');
		
		$this->publishes([
			realpath(__DIR__.'/migrations') => database_path('migrations'),
		]);
        
		//if tables exists
		if($this->checkTablesExists()) {
			$config = $this->app['config']->get('multilanguage.route', []);
			if(!isset($config['namespace']))
				$config['namespace'] = 'Simexis\MultiLanguage\Controllers';

			$router->group($config, function($router)
			{
				$router->get('view/{group}', 'MultilanguageController@getView');
				$router->controller('/', 'MultilanguageController');
			});
		}
		
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['translator', 'translation.loader', 'translation.provider', 'translation.provider.entry'];
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	private function checkTablesExists()
	{
		static $check;
		if(!is_null($check))
			return $check;
		try {
			return $check = DB::table('languages')->exists() && DB::table('language_entries')->exists();
		} catch(\Exception $e) {
			return $check = false;
		}
	}

}
