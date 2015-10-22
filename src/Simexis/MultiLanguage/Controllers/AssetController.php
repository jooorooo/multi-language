<?php

namespace Simexis\MultiLanguage\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Simexis\MultiLanguage\Helpers\Render;

class AssetController extends Controller {
	
	private $render;
	
	public function __construct(Render $render) {
		$this->render = $render; 
	}
	
    /**
     * Return the javascript for the Debugbar
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getJs()
    {
        $renderer = $this->render->getJavascriptRenderer();

        $content = $renderer->dumpAssetsToString('js');

        $response = new Response(
            $content, 200, array(
                'Content-Type' => 'text/javascript',
            )
        );

        return $this->cacheResponse($response);
    }

    /**
     * Return the stylesheets for the Debugbar
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getCss()
    {
        $renderer = $this->render->getStylesheetRenderer();

        $content = $renderer->dumpAssetsToString('css');

        $response = new Response(
            $content, 200, array(
                'Content-Type' => 'text/css',
            )
        );

        return $this->cacheResponse($response);
    }

    /**
     * Cache the response 1 year (31536000 sec)
     */
    protected function cacheResponse(Response $response)
    {
        //$response->setSharedMaxAge(31536000);
        //$response->setMaxAge(31536000);
        //$response->setExpires(new \DateTime('+1 year'));

        return $response;
    }
	
}