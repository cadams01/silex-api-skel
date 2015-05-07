<?php

/**
 * Token created for user data during authentication
 *
 * @author eschwartz <erics273@gmail.com>
 * @since  2013-11-25
 * 
 */

namespace API\Authentication\WSSE;

use \Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class WsseUserToken extends AbstractToken
{
    /**
     * Holds data from auth header
     * @var string
     */ 
    public $created;
    public $digest;
    public $nonce;

    /**
     * takes roles from authenticated user, if we have roles then authenticated is true.
     * @param array $roles [description]
     */
    public function __construct(array $roles = array())
    {
        parent::__construct($roles);

        // If the user has roles, consider it authenticated
        $this->setAuthenticated(count($roles) > 0);
    }

    /**
     * implemente from interface
     * dont want to use this at the moment
     * @return null
     */
    public function getCredentials()
    {
        return null;
    }
}