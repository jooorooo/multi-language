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
use Simexis\MultiLanguage\Helpers\Render;

class MultiLanguageServiceProvider extends TranslationServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;
	
	private $commands = [
		'AppendCommand',
		'ReplaceCommand',
		'TruncateCommand',
		'ClearCommand',
	];
	
	private static $NAMESPACE = '\\Simexis\\MultiLanguage\\Commands\\';

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
		$this->registerManager();

		foreach($this->commands AS $command)
			$this->commands(static::$NAMESPACE . $command);
		
		$this->registerTranslator();
		$this->registerRender();
		
    }

	/**
	 * Register the translation line loader.
	 *
	 * @return void
	 */
	protected function registerLoader()
	{ 
		$this->app->bindShared('translator.loader', function($app)
		{ 
			$languageProvider 	= new LanguageProvider();
			$langEntryProvider 	= new LanguageEntryProvider();

			$mode = $app['config']->get('multilanguage.mode');

			if ($mode == 'auto' || empty($mode))
				$mode = ($this->app['config']->get('app.debug') ? 'mixed' : 'database');
			
			if(!$this->checkTablesExists())
				$mode = 'filesystem';
			
			$this->app->bindShared('translator.provider', function($app) use($languageProvider){
				return $languageProvider;
			});
			
			$this->app->bindShared('translator.provider.entry', function($app) use($langEntryProvider){
				return $langEntryProvider;
			});
			
			$this->app->bindShared('translator.fileloader', function($app) use($languageProvider, $langEntryProvider){
				return new FileLoader($languageProvider, $langEntryProvider, $app);
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
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function registerManager()
	{
		$this->app->bindShared('translator.manager', function($app)
		{
			$provider = $app['translator.provider'];
			$entry = $app['translator.provider.entry'];
			$fileLoader = $app['translator.fileloader'];

			$manager = new Manager($provider, $entry, $fileLoader);

			return $manager;
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
			$loader = $app['translator.loader'];

			// When registering the translation component, we'll need to set the default
			// locale as well as the fallback locale. So, we'll grab the application
			// configuration so we can easily get both of these values from there.
			$locale = $app['config']->get('app.locale');

			$trans = new Translator($loader, $locale);

			return $trans;
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function registerRender()
	{
		$this->app->bindShared('Simexis\MultiLanguage\Helpers\Render', function ($app) {
            return new Render($app);
        });
		
		foreach(app('files')->allFiles(__DIR__ . '/Assets') AS $file)
			app('Simexis\MultiLanguage\Helpers\Render')->setAssets($file->getPathname());
	}

	/**
	 * Bootstrap the application events.
	 *
     * @param \Illuminate\Routing\Router  $router
	 * @param \Simexis\MultiLanguage\Manager $manager
	 * @return void
	 */
    public function boot(Router $router, Manager $manager)
    { 
        $this->loadViewsFrom(__DIR__.'/resources/views', 'multilanguage');
		$this->loadTranslationsFrom(__DIR__.'/Lang', 'multilanguage');
			
        $this->publishes([
            __DIR__.'/resources/views' => base_path('resources/views/vendor/multilanguage'),
        ], 'views');
		
		$this->publishes([
			realpath(__DIR__.'/migrations') => database_path('migrations'),
		]);
		
        $this->publishes([
            __DIR__.'/Lang' => base_path('resources/lang'),
        ]);

		//if tables exists
		if($this->checkTablesExists()) {
			$config = $this->app['config']->get('multilanguage.route', []);
			if(!isset($config['namespace']))
				$config['namespace'] = 'Simexis\MultiLanguage\Controllers';

			$router->group($config, function($router)
			{
				$router->get('view/{group}', 'MultilanguageController@getView');
				$router->controller('/assets', 'AssetController');
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
		$providers = [
			'translator', 
			'translator.loader', 
			'translator.provider', 
			'translator.provider.entry', 
			'translator.manager', 
			'translator.fileloader',
			'Simexis\MultiLanguage\Helpers\Render'
		];
		foreach($this->commands AS $command)
			$providers[] = static::$NAMESPACE . $command;
			
		return $providers;
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
			DB::table('languages')->exists() && DB::table('language_entries')->exists();
			return $check = true;
		} catch(\Exception $e) {
			return $check = false;
		}
	}

}
