<?php
// All baz.waldo, foo.baz.waldo end up in the same dir
$hostdir = basename(dirname($_SERVER['SCRIPT_FILENAME'])); // /public/www/app.php -> www
$https = (!empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'off'));
$uri = $_SERVER['REQUEST_URI'];
// Is this (?:(foo.)|www.(bar.))?(baz.waldo)
if (preg_match('/^(?:([^.]+\.)|www\.([^.]+\.))?([^.]+\.[^.]+)\z/', $_SERVER['HTTP_HOST'], $m)) {
	if (($m[1] !== "{$hostdir}." || !$https) && empty($m[2])) {
		// baz.waldo or foo.baz.waldo -> www.baz.waldo if foo is not known, also HTTP -> HTTPS
		header("Location: https://{$hostdir}.{$m[3]}{$uri}", true, 301);
		exit;
	} elseif (empty($m[1])) {
		// www.bar.baz.waldo -> https://bar.baz.waldo
		header("Location: https://{$m[2]}{$m[3]}{$uri}", true, 301);
		exit;
	}
}

// Uncomment this line if you must temporarily take down your site for maintenance.
// require '.maintenance.php';

// Let bootstrap create Dependency Injection container.
$container = require __DIR__ . '/../../app/bootstrap.php';

// Run application.
$container->application->run();
