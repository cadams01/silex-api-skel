<?php

/**
 *
 * Extended Silex\Application to add some custom functionality/organization
 *
 * @author  escwhartz
 * @since 2013-11-25
 *
 * @todo Find a better way to handle headers in JSON response methods for cross domain issues, build in standard API logging
 *
 */

namespace API;

//some common compenets needed for the application
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpKernel\Exception\HttpException;
use API\Controller;
use API\Exception;
use API\Authentication\WSSE;

class Application extends \Silex\Application
{

    /**
     * setup application instance
     */
    public function __construct()
    {
        parent::__construct();
        $this->registerParameters();
        $this->registerServiceProviders();
       // $this->registerSecurity();
        $this->registerRoutes();
        $this->registerErrorHandler();
    }

    /**
     * Register parameters
     *
     * @return void
     */
    private function registerParameters()
    {

        $this['debug'] = false;

    }

    /**
     * Register silex service providers
     *
     * @return void
     */
    private function registerServiceProviders()
    {

        $this->register(new \Silex\Provider\SecurityServiceProvider(),
            array('security.firewalls' => array()));
    }

    /**
     * setup security on the application
     * @return void
     */
    private function registerSecurity()
    {

        // Fix for 5.3 (still needed with php5.4 as silex does not support the rebind)
        // used for non controllers as services routes
        $app = $this;

        //register our WSSE security classes on the app as a service so we can use WSSE as a security type on the firewall
        WSSE\WsseSecurity::registerSecurity($app);

        //build firewalls to securure application
        //login has no secuirty and is used to authenticate the web users
        $this['security.firewalls'] = array(
            'login' => array(
                'pattern' => '^/login$',
            ),
            'api' => array(
                'pattern' => '^.*$',
                'security' => true,
                'stateless' => true,
                'wsse' => array(
                    "cacheDir" => stristr(strtolower($_SERVER[HTTP_HOST]), "localhost") ? "/tmp/security_cache" : "/data/script_data/api.saveonenergy.com/security_cache"
                )
            )
        );

    }

    /**
     * Make sure request is sent as JSON
     * @param  Request $request
     * @return string
     */
    public function validateRequest(Request $request)
    {

        $method = $request->getMethod();

        //if your not sending data then your request is valid
        if ($method === 'GET' || $method === 'OPTIONS' || $method === 'DELETE') {
            return true;
        }

        $contentType = $request->headers->get('Content-Type');
        if (0 === strpos($contentType, 'application/json')) {

            $data = json_decode($request->getContent(), true);

            if ($data === null) {
                $this->issueError("JSON could not be validated");
            }

        } else {
            $this->issueError("The API expects JSON, {$contentType} was received");
        }


    }

    /**
     *
     * method to issue a response
     *
     * @param $data
     * @param int $status
     * @param null $cacheTTL
     * @param array $headers
     * @return mixed
     */
    public function respond($data = '', $status = 200, $cacheTTL = null, $headers = array(
                                          "Access-Control-Allow-Origin" => "*",
                                          "Access-Control-Allow-Methods" => "POST, GET, PUT, DELETE, OPTIONS",
                                          "Access-Control-Allow-Headers" => "X-Requested-With, Content-Type")
    )
    {

        if ($data === '') {
            $response = new Response($data, $status, $headers);
        } else {
            $response = $this->json($data, $status, $headers);
        }

/*
        if (is_int($cacheTTL) && $response->isCacheable()) {
            $response->setPublic();
            $response->setSharedMaxAge($cacheTTL);
        }
*/
        
        return $response;

    }

    /**
     *
     * error out
     *
     * @param $message
     * @param int $status
     */
    public function issueError($message, $status = 500)
    {

        $this->abort($status, $message);

    }

    /**
     * Method to issue an email notification
     * @param $subject
     * @param $message
     */
    public function issueNotification($subject, $message, $recipientArray = array("es_it@redventures.com"))
    {

        //some notification code here

    }

    /**
     * Error handler for reporting application errors to user
     */
    private function registerErrorHandler()
    {

        $app = $this;

        // Error Handler for Production
        if (!$this['debug']) {
            $this->error(function (\Exception $e, $code) use ($app) {

                if( !($e instanceof HttpException) ){

                    //figure out environment from url
                    $matches = array();
                    $env = preg_match("/^[^\-]*/",$_SERVER["HTTP_HOST"], $matches);
                    $env = strtoupper(str_replace("energy", "production", $matches[0]));

                    $message = "
                        <b>Exception Message:</b>
                        <pre>".$e->getMessage()."</pre>
                        <b>Server:</b>
                        <pre>".php_uname('n')." (".$env.")</pre>
                        <b>File:</b>
                        <pre>".$e->getFile()." - (Line: ".$e->getLine().")</pre>
                        <b>Exception Trace:</b>
                        <pre>".$e->getTraceAsString()."</pre>
                    ";

                    $recipientArray = array("es_it@redventures.com");

                    //add marketing dev if cart is true. probably should use a distro but stephen wanted it this way for now.
                    $request = $app["request"];
                    if( $request->query->get("cart") ){
                        $recipientArray[] = "ssylvester@redventures.com";
                        $env .= " - CART";
                    }

                    \ES_Error::notify_generic("(".$env.") EnergyAPI Exception: ".$e->getMessage()." on (" . php_uname('n') .")", $message, $recipientArray );


                    $eventData = array('CompanyID' => 25,
                      'ObjectPath' => 'energysavings/api/energyapi/exception count',
                      'EventType' => \ES_Console_Event::TYPE_PERFORMANCE,
                      'StatusID' => \ES_Console_Event::STATUS_INFORMATIONAL,
                      'EventData' => array('value' => 1, 'aggregate' => 'sum')
                    );

                    try{
                      \ES_Console_Event::log_event($eventData);
                    }catch (\Exception $E){
                      //let someone know we had an issue logging performace data
                      \ES_Error::notify_generic("Unable to log performace data", $E->getMessage(), array("es_it@redventures.com"), 3);
                    }


                  //\ES_Console_Event::log_event(\ES_Console_Event::TYPE_INFORMATIONAL, gethostname(), 'EnergyAPI', $app['partnerClass'], 'Exception', \ES_Console_Event::STATUS_INFORMATIONAL, $e->getMessage(), '');

                }
                return new Response($e->getMessage(), $code);
            });
        }

    }

    /**
     * Register silex routes
     *
     * @return void
     */
    private function registerRoutes()
    {

        $this->genericRoutes($this);

    }

    private function genericRoutes(\Silex\Application $app)
    {

        $app->get('{url}', function () use ($app) {
            return $app->respond();
        })->method('OPTIONS')->assert('url', '.+');

        $app->get('/', function () use ($app) {
            $app->issueError("Go Away", 501);
        });
    }


}
