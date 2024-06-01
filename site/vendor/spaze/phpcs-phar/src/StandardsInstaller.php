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
			Config::setConfigData('installed_paths', implode(',', $paths), true);
		}
	}

}
