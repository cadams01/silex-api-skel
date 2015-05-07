<?php

/**
 *
 * WSSE Security Listener that handles the validation of the authentication
 * This class brakes the header into the parts needed to generate the user token for validation. 
 *
 * @author eschwartz <erics273@gmail.com>
 * @since 2013-11-25
 *
 * @todo  handle logging on authentication failure.
 * 
 */

namespace API\Authentication\WSSE;

use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Event\GetResponseEvent;
use \Symfony\Component\Security\Http\Firewall\ListenerInterface;
use \Symfony\Component\Security\Core\Exception\AuthenticationException;
use \Symfony\Component\Security\Core\SecurityContextInterface;
use \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use \API\Authentication\WSSE\WsseUserToken;

class WsseListener implements ListenerInterface
{
    /**
     * Security contect on the application $app["security"]
     * @see \Symfony\Component\Security\Core\SecurityContext
     */
    protected $securityContext;

    /**
     * Class that will do the authentication of the token
     * @var \API\Authentication\WSSE\WsseProvider
     * @see \Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface
     */
    protected $authenticationManager;

    public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager)
    {
        $this->securityContext = $securityContext;
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * Method to handle the data from the X-WSSE header
     * @param  GetResponseEvent $event
     * @return mixed
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        $wsseRegex = '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/';
        if (!$request->headers->has('x-wsse') || 1 !== preg_match($wsseRegex, $request->headers->get('x-wsse'), $matches)) {
            $response = new Response();
            $response->setStatusCode(403);
            return $event->setResponse($response);
        }

        $token = new WsseUserToken();
        $token->setUser($matches[1]);

        $token->digest   = $matches[2];
        $token->nonce    = $matches[3];
        $token->created  = $matches[4];

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->securityContext->setToken($authToken);

            return;
        } catch (AuthenticationException $failed) {

            /**
             * add code here to notify someone of bad login
             */

            // Deny authentication with a '403 Forbidden' HTTP response
            $response = new Response();
            $response->setStatusCode(403);
            return $event->setResponse($response);

        }

        // By default deny authorization
        $response = new Response();
        $response->setStatusCode(403);
        return $event->setResponse($response);
    }
}