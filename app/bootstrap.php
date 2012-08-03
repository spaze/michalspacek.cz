<?php

/**
 * The michalspacek.cz bootstrap file.
 */
use \Nette\Application\Routers\Route;


// Load Nette Framework
require LIBS_DIR . '/Nette/loader.php';

// Hide Nette banner
if (!headers_sent()) {
	header('X-Driven-By: Success');
	header('X-Powered-By: Failure');
}

// Configure application
$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
$configurator->setDebugMode(ENVIRONMENT == 'development');
$configurator->enableDebugger(__DIR__ . '/../log');

// Enable RobotLoader - this will load all classes automatically
$configurator->setTempDirectory(__DIR__ . '/../temp');
$configurator->createRobotLoader()
	->addDirectory(APP_DIR)
	->addDirectory(LIBS_DIR)
	->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . '/config/config.neon', ENVIRONMENT);
$container = $configurator->createContainer();

// Setup router
Route::addStyle('name', 'action');  // let the name param be converted like the action param (foo-bar => fooBar)
$container->router[] = new Route('skoleni/<name>[/<action>[/<param>]]', 'Skoleni:skoleni');
$container->router[] = new Route('<presenter>[/<action>]', 'Homepage:default');

$httpResponse = $container->httpResponse;
$container->application->onStartup[] = function() use ($httpResponse) {
	$httpResponse->setHeader('X-Content-Type-Options', 'nosniff');
	$httpResponse->setHeader('X-XSS-Protection', '1; mode=block');
};

// Configure and run the application!
$container->application->run();
