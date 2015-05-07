<?php

/**
 * User class
 *
 * holds info about a user when needed for checking roles, authnticating, etc..
 *
 * @author  eschwartz <erics273@gmail.com>
 * @since 2013-11-25
 * 
 */

namespace API\Authentication;

use \Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    /**
     * ID of user
     * @var int
     */
    private $userID;

    /**
     * Username
     * @var string
     */
    private $username;

    /**
     * Users password
     * @var string
     */
    private $password;

    /**
     * Users Roles
     * @var array
     */
    private $roles;

    /**
     * takes user from DB_CommercialBrokerage_Users and stores information in this class
     * @param \Symfony\Component\Security\Core\User $user
     */
    public function __construct(\Symfony\Component\Security\Core\User $User)
    {

        //loop through user info and convert key to camelCase to conform to PSR standard and store as properties on the class
        //do data transformations as needed
        foreach ($User as $key => $value) {

            $key = lcfirst($key);

            if(property_exists(__CLASS__, $key)){
               
                switch($key){
                    //turn roles into an array of roles and remove spaces if they exist
                    case "roles":
                        $this->$key = explode(",", str_replace(" ", "", $user->Roles));
                        break;
                    default:
                        $this->$key = $value;

                }
                
            }
        }
    }

    /**
     * Getter methods for properties on the class
     */
    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getUserID()
    {
        return $this->userID;
    }

    public function eraseCredentials()
    {
        return null;
    }

}