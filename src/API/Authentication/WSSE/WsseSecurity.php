<?php

namespace API\Authentication\WSSE;

use API\Authentication;

class WsseSecurity
{

    /**
     * register wsse security on application
     * @return void 
     */
    public static function registerSecurity(\Silex\Application $App){

    	$App['security.authentication_listener.factory.wsse'] = $App->protect( function ($name, $options) use ($App) {

	            // define the authentication provider object
	            $App['security.authentication_provider.'.$name.'.wsse'] = $App->share(function () use ($options) {
	                return new WsseProvider( new Authentication\UserProvider(), $options);
	            });

	            // define the authentication listener object
	            $App['security.authentication_listener.'.$name.'.wsse'] = $App->share(function () use ($App) {
	                return new WsseListener($App['security'], $App['security.authentication_manager']);
	            });

	            return array(
	                // the authentication provider id
	                'security.authentication_provider.'.$name.'.wsse',
	                // the authentication listener id
	                'security.authentication_listener.'.$name.'.wsse',
	                // the entry point id
	                null,
	                // the position of the listener in the stack
	                'pre_auth'
	            );
	        
		});

    }


}