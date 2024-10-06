<?php
declare(strict_types = 1);

namespace Spaze\PHPCSPhar;

use Phar;

class Autoloader
{

	public static function load(): void
	{
		$autoloadFiles = [
			__DIR__ . '/../vendor/autoload.php',
			__DIR__ . '/../../../autoload.php',
		];

		$autoloadLoaded = false;
		foreach ($autoloadFiles as $autoloadFile) {
			if (is_file($autoloadFile)) {
				require_once $autoloadFile;
				$autoloadLoaded = true;
				break;
			}
		}

		if (!$autoloadLoaded) {
			fwrite(STDERR, "Install packages using Composer.\n");
			exit(254);
		}

		Phar::loadPhar(__DIR__ . '/../phpcs.phar', 'phpcs.phar');
		require_once 'phar://phpcs.phar/autoload.php';
	}

}
