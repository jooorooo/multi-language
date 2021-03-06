<?php 

namespace Simexis\MultiLanguage;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Simexis\MultiLanguage\Helpers\Render;
use Simexis\MultiLanguage\Facades\Translator;
use Simexis\MultiLanguage\Loaders\FileLoader;
use Simexis\MultiLanguage\Loaders\MixedLoader;
use Simexis\MultiLanguage\Loaders\DatabaseLoader;
use Simexis\MultiLanguage\Generators\UrlGenerator;
use Simexis\MultiLanguage\Providers\LanguageProvider;
use Illuminate\Translation\TranslationServiceProvider;
use Simexis\MultiLanguage\Providers\LanguageEntryProvider;

class MultiLanguageServiceProvider extends TranslationServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;
	
	protected $commands = [
		'AppendCommand',
		'ReplaceCommand',
		'TruncateCommand',
		'ClearCommand',
	];
	
	protected static $NAMESPACE = '\\Simexis\\MultiLanguage\\Commands\\';

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
		$this->registerUrlGenerator();
		
    }

	/**
	 * Register the translation line loader.
	 *
	 * @return void
	 */
	protected function registerLoader()
	{ 
		$this->app->singleton('translator.loader', function($app)
		{ 
			$languageProvider 	= new LanguageProvider();
			$langEntryProvider 	= new LanguageEntryProvider();

			$mode = $app['config']->get('multilanguage.mode');

			if ($mode == 'auto' || empty($mode))
				$mode = ($this->app['config']->get('app.debug') ? 'mixed' : 'database');
			
			if(!$this->checkTablesExists())
				$mode = 'filesystem';
			
			$this->app->singleton('translator.provider', function($app) use($languageProvider){
				return $languageProvider;
			});
			
			$this->app->singleton('translator.provider.entry', function($app) use($langEntryProvider){
				return $langEntryProvider;
			});
			
			$this->app->singleton('translator.fileloader', function($app) use($languageProvider, $langEntryProvider){
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
		$this->app->singleton('translator.manager', function($app)
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
		$this->app->singleton('translator', function($app)
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
     * Register the URL generator service.
     *
     * @return void
     */
    protected function registerUrlGenerator()
    {
        $this->app['url'] = $this->app->share(function ($app) {
            $routes = $app['router']->getRoutes();

			$this->beforeRoute();
			
            // The URL generator needs the route collection that exists on the router.
            // Keep in mind this is an object, so we're passing by references here
            // and all the registered routes will be available to the generator.
            $app->instance('routes', $routes);

            $url = new UrlGenerator(
                $routes, $app->rebinding(
                    'request', $this->requestRebinder()
                )
            );

            $url->setSessionResolver(function () {
                return $this->app['session'];
            });

            // If the route collection is "rebound", for example, when the routes stay
            // cached for the application, we will need to rebind the routes on the
            // URL generator instance so it has the latest version of the routes.
            $app->rebinding('routes', function ($app, $routes) {
                $app['url']->setRoutes($routes);
            });

            return $url;
        });
		
    }
	
	protected function beforeRoute() {
		if(App::runningInConsole())
			return;
		
		$request = request();
		$locales = app('translator.manager')->getLocales(); 
		if(!$locales)
			$locales = [app()->getLocale() => app()->getLocale()];
		if(array_key_exists($request->segment(1), $locales)) {
			$this->serverModify($request, $locales);
		}
	}
	
	protected function serverModify($request, $locales) {
		$this->app->setLocale($request->segment(1));
		$path = substr($request->path(), 1) == '/' ? substr($request->path(), 0, 1) : $request->path();
		if(array_key_exists(strtolower($path), $locales)) {
			$serverpath = explode('/' . $path, $request->server->get('REQUEST_URI'));
			$request->server->set('REQUEST_URI', $serverpath[0] . '/');
		} else {
			$serverpath = $request->server->get('REQUEST_URI');
			$serverpath = str_replace($path, preg_replace('~^' . $request->segment(1) . '\/~i','',$path), $serverpath);
			$request->server->set('REQUEST_URI', rtrim($serverpath, '/') . '/');
		}
		
		$request->initialize(
			$request->query->all(), 
			$request->request->all(), 
			$request->attributes->all(), 
			$request->cookies->all(), 
			$request->files->all(), 
			$request->server->all(), 
			$request->server->getHeaders()
		);
	}

    /**
     * Get the URL generator request rebinder.
     *
     * @return \Closure
     */
    protected function requestRebinder()
    {
        return function ($app, $request) {
            $app['url']->setRequest($request);
        };
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function registerRender()
	{
		$this->app->singleton('Simexis\MultiLanguage\Helpers\Render', function ($app) {
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
			if(!isset($config['middleware']) && version_compare(app()->version(), '5.2', '>=')) 
				$config['middleware'] = ['web'];

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
			'Simexis\MultiLanguage\Helpers\Render',
			'url',
			'route'
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
	protected function checkTablesExists()
	{
		static $check;
		if(!is_null($check))
			return $check;
		try {
			$check = Schema::hasTable('languages') && Schema::hasTable('language_entries');
			return $check;
		} catch(\Exception $e) {
			return $check = false;
		}
	}

}
