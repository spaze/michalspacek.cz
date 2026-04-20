<?php
declare(strict_types = 1);

// Used as Nette Tester's bootstrap (in each test) and as Psalm's autoloader (psalm.xml)
require __DIR__ . '/../vendor/autoload.php';

$vendorDevAutoload = __DIR__ . '/../vendor-dev/vendor/autoload.php';
if (!is_file($vendorDevAutoload)) {
	throw new RuntimeException('Missing development autoloader at "' . $vendorDevAutoload . '", run "composer --working-dir=vendor-dev install" and try again.');
}
require $vendorDevAutoload;
