<?php
declare(strict_types = 1);

use Composer\InstalledVersions;

require __DIR__ . '/../vendor/autoload.php';

/**
 * @return array{libPrettyVersion:string, libVersion:string, libReference:string}
 */
return function (): array {
	return [
		'libPrettyVersion' => InstalledVersions::getPrettyVersion('spaze/security-txt'),
		'libVersion' => InstalledVersions::getVersion('spaze/security-txt'),
		'libReference' => InstalledVersions::getReference('spaze/security-txt'),
	];
};
