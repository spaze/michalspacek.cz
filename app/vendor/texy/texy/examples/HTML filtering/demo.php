<?php

/**
 * This demo shows how Texy! control inline html tags
 *     - three safe levels
 *     - full control over all tags and attributes
 *     - (X)HTML reformatting
 *     - well formed output
 */

declare(strict_types=1);


if (@!include __DIR__ . '/../vendor/autoload.php') {
	die('Install packages using `composer install`');
}


$texy = new Texy;
$texy->htmlOutputModule->baseIndent = 1;


function doIt($texy)
{
	// processing
	$text = file_get_contents('sample.texy');
	$html = $texy->process($text);  // that's all folks!

	// echo formated output
	echo $html;

	// and echo generated HTML code
	echo '<pre>';
	echo htmlspecialchars($html);
	echo '</pre>';
	echo '<hr />';
}


header('Content-type: text/html; charset=utf-8');

echo '<h2>Enable nearly all valid tags</h2>';
// by default
doIt($texy);

echo '<h2>Texy::ALL - enables all tags</h2>';
$texy->allowedTags = $texy::ALL;
doIt($texy);

echo '<h2>safeMode() - enables only some "safe" tags</h2>';
Texy\Configurator::safeMode($texy);
doIt($texy);

echo '<h2>disableLinks() - disable all links</h2>';
Texy\Configurator::disableLinks($texy);
doIt($texy);

echo '<h2>Texy::NONE - disables all tags</h2>';
$texy->allowedTags = $texy::NONE;
doIt($texy);

echo '<h2>Enable custom tags</h2>';
$texy->allowedTags =
	[ // enable only tags <my-extraTag> with attribute & <strong>
		'my-extraTag' => ['attr1'],
		'strong' => [],
	];
doIt($texy);
