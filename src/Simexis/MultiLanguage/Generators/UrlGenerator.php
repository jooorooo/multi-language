<?php 

namespace Simexis\MultiLanguage\Generators;

use Illuminate\Routing\UrlGenerator AS BaseUrlGenerator;

class UrlGenerator extends BaseUrlGenerator
{

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

    /**
     * Generate an absolute URL to the given path.
     *
     * @param  string  $path
     * @param  mixed  $extra
     * @param  bool|null  $secure
     * @return string
     */
    public function to($path, $extra = [], $secure = null)
    {
        // First we will check if the URL is already a valid URL. If it is we will not
        // try to generate a new one but will simply return the URL as is, which is
        // convenient since developers do not always have to check if it's valid.
        if ($this->isValidUrl($path)) {
            return $path;
        }

        $scheme = $this->getScheme($secure);

        $extra = $this->formatParameters($extra);

        $tail = implode('/', array_map(
            'rawurlencode', (array) $extra)
        );

        // Once we have the scheme we will compile the "tail" by collapsing the values
        // into a single string delimited by slashes. This just makes it convenient
        // for passing the array of parameters to this URL as a list of segments.
        $root = $this->getRootUrl($scheme);

        return $this->trimUrl($root, $this->uriWithLocale($path), $tail);
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
		$locales = $this->getLocales();
		if (count($segments) && in_array(strtolower($segments[0]), $locales)) {
			$newUri = "/{$locale}";
			for($i = 1; $i < sizeof($segments); $i++) {
				$newUri .= "/{$segments[$i]}";
			}
		}
		return $newUri;
	}
	
}