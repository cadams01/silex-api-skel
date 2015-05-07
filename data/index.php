<?php

/**
 *
 * This is the application file for the EnergyAPI
 *
 * This files setups the silex app that is the backbone for the EnergyAPI
 *
 * @author eschwartz <erics273@gmail.com>
 * @since 2013-11-25
 *
 */


// Spin up Silex
require_once '../vendor/autoload.php';
// intranet define

use API\Application;

//fire up the application
$app = new Application();

//Some setup stuff for the application Request
$app->before(array($app, "validateRequest"));

//run the application
$app->run();