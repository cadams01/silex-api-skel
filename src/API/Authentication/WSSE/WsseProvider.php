<?php 

/**
 * Class that actually does the WSSE authentication. Takes the pieces of the header and validates them.
 *
 * @author eschwartz <eschwartz@redventures.com>
 * @since 2013-11-25
 * 
 */

namespace API\Authentication\WSSE;

use \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use \Symfony\Component\Security\Core\User\UserProviderInterface;
use \Symfony\Component\Security\Core\Exception\AuthenticationException;
use \Symfony\Component\Security\Core\Exception\NonceExpiredException;
use \Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use \BrokerageAPI\Authentication\WSSE\WsseUserToken;

class WsseProvider implements AuthenticationProviderInterface
{
    /**
     * class that provides user data for authentication
     * @see \Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface
     * @see \API\Authentication\UserProvider
     */
    private $userProvider;

    /**
     * Cache directory for storing Nonces and times for ttl validation
     * @var string
     */
    private $cacheDir;

    /**
     * time in seconds that unused NONCE should be valid for
     * @var integer
     */
    private $ttl = 300;

    /**
     * set the provided options passing in from the firewall and user provider on the class
     * @param UserProviderInterface $userProvider
     * @param array $options
     */
    public function __construct(UserProviderInterface $userProvider, array $options = array())
    {
        $this->userProvider = $userProvider;
        
        if(!empty($options)){
            //if item in options array exists and property in this class, set it
            foreach ($options as $key => $value) {
                if(property_exists(__CLASS__, $key)){
                    $this->$key = $value;
                }
            }

        }

    }

    /**
     * authenticate the user based on passed in token setup in WsseListener
     * @param  TokenInterface $token
     * @return mixed
     */
    public function authenticate(TokenInterface $token)
    {
        //load user details by username
        $user = $this->userProvider->loadUserByUsername($token->getUsername());

        //validate user info from provider against user info passed in from token and return authtoken or error
        if ($user && $this->validateDigest($token->digest, $token->nonce, $token->created, $user->getPassword())) {
            $authenticatedToken = new WsseUserToken($user->getRoles());
            $authenticatedToken->setUser($user);

            return $authenticatedToken;
        }

        throw new AuthenticationException('The WSSE authentication failed.');
    }

    /**
     * Validate password digest sent in WSSE header 
     * @param  string $digest
     * @param  string $nonce
     * @param  string $created
     * @param  string $secret
     * @return bool
     */
    protected function validateDigest($digest, $nonce, $created, $secret)
    {
        // Check created time is not in the future
        // Servers cant seem to keep time in sync so i am removing this check for now eschwartz 10-16-13
        // if (strtotime($created) > time()) {
        //     throw new AuthenticationException("Created time is in the future");
        // }

        // Expire timestamp after 5 minutes
        if (time() - strtotime($created) > $this->ttl) {
           throw new AuthenticationException("Authentication expired");
        }

        // Validate nonce is unique within 5 minutes
        if (file_exists($this->cacheDir.'/'.$nonce) && file_get_contents($this->cacheDir.'/'.$nonce) + $this->ttl > time()) {
            throw new NonceExpiredException('Previously used nonce detected');
        }
        // If cache directory does not exist we create it
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
        file_put_contents($this->cacheDir.'/'.$nonce, time());

        // Validate Secret
        $expected = base64_encode(sha1(base64_decode($nonce).$created.$secret, true));

        return $digest === $expected;
    }

    /**
     * required method to see if this class supports your token
     * @param  TokenInterface $token
     * @return bool
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof WsseUserToken;
    }
}