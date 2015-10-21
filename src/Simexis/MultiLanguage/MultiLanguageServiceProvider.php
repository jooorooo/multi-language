<?php 

namespace Simexis\MultiLanguage;

use Illuminate\Support\ServiceProvider;

class MultiLanguageServiceProvider extends ServiceProvider {

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
	
    }

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
    public function boot()
    { 
		
    }

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}

}
