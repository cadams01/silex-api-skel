<?php

/**
 * User provider class to get user data from source and provide to security provider for auth purposes
 *
 * @author  eschwartz <erics273@gmail.com>
 * @since  2013-11-25
 * 
 */

namespace API\Authentication;

use \Symfony\Component\Security\Core\User\UserProviderInterface;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use \Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use \API\Authentication\User;

class UserProvider implements UserProviderInterface
{

    /**
     * get user data by username
     * @param  string $username
     * @return ??
     */
    public function loadUserByUsername($username)
    {
        /**
         * get your user from some source here
         */
        $user = null;

        if (!$user) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }

        return new User($user);
    }

    /**
     * reload user data
     * @param  UserInterface $user [description]
     * @return \BrokerageAPI\Authentication\User
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * method to see if this suports your user class
     * @param  string $class 
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === '\API\Authentication\User';
    }
}