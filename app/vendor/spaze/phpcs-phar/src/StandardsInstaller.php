<?php
declare(strict_types = 1);

namespace Spaze\PHPCSPhar;

use Composer\InstalledVersions;
use PHP_CodeSniffer\Config;

class StandardsInstaller
{

	public static function install(): void
	{
		if (!Config::getConfigData('installed_paths')) {
			$packages = InstalledVersions::getInstalledPackagesByType('phpcodesniffer-standard');
			$packages = array_unique($packages);
			sort($packages);
			$paths = [];
			foreach ($packages as $package) {
				$paths[] = InstalledVersions::getInstallPath($package);
			}
			// This seems to be the only way to programmatically set temporary config options in PHPCS 4.0+
			$_SERVER['argv'][] = '--runtime-set';
			$_SERVER['argv'][] = 'installed_paths';
			$_SERVER['argv'][] = implode(',', $paths);
		}
	}

}
