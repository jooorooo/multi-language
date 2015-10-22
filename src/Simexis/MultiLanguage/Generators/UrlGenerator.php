<?php 

namespace Simexis\MultiLanguage\Generators;

use Illuminate\Routing\UrlGenerator AS BaseUrlGenerator;

class UrlGenerator extends BaseUrlGenerator
{
	
    /**
     * Get the URL to a named route.
     *
     * @param  string  $name
     * @param  mixed   $parameters
     * @param  bool  $absolute
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        if (! is_null($route = $this->routes->getByName($name))) {
            return $this->toRoute($route, $parameters, $absolute);
        }

        throw new InvalidArgumentException("Route [{$name}] not defined.");
    }

    /**
     * Get the URL for a given route instance.
     *
     * @param  \Illuminate\Routing\Route  $route
     * @param  mixed  $parameters
     * @param  bool   $absolute
     * @return string
     */
    protected function toRoute($route, $parameters, $absolute)
    {
        $parameters = $this->formatParameters($parameters);

        $domain = $this->getRouteDomain($route, $parameters);

        $uri = strtr(rawurlencode($this->addQueryString($this->trimUrl(
            $root = $this->replaceRoot($route, $domain, $parameters),
            $this->uriWithLocale($this->replaceRouteParameters($route->uri(), $parameters))
        ), $parameters)), $this->dontEncode);
		
        return $absolute ? $uri : '/'.ltrim(str_replace($root, '', $uri), '/');
    }
	
	private function getManager() {
		return app('translator.manager');
	}
	
	private function getLocales() {
		return $this->getManager()->getLocales();
	}
	
	private function getLocale() {
		return app()->getLocale();
	}

	/**
	*  Transforms a uri into one containing the current locale slug.
	*  Examples: login/ => /es/login . / => /es
	*
	*  @param string $uri Current uri.
	*  @return string Target uri.
	*/
	private function uriWithLocale($uri)
	{
		$locale = $this->getLocale();
		// Delete the forward slash if any at the beginning of the uri:
		$uri = substr($uri, 0, 1) == '/' ? substr($uri, 1) : $uri;
		$segments = explode('/', $uri);
		$newUri = "/{$locale}/{$uri}";
		if (count($segments) && strlen($segments[0]) == 2) {
			$newUri = "/{$locale}";
			for($i = 1; $i < sizeof($segments); $i++) {
				$newUri .= "/{$segments[$i]}";
			}
		}
		return $newUri;
	}
	
}