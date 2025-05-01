<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use Composer\InstalledVersions;
use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\ShouldNotHappenException;

final readonly class SecurityTxtLibraryVersion
{

	public const string PACKAGE_NAME = 'spaze/security-txt';


	public function getInstalled(): DependencyVersion
	{
		$version = InstalledVersions::getVersion(self::PACKAGE_NAME);
		$reference = InstalledVersions::getReference(self::PACKAGE_NAME);
		if ($version === null || $reference === null) {
			throw new ShouldNotHappenException(sprintf('Package %s seems to be not installed', self::PACKAGE_NAME));
		}
		return new DependencyVersion($version, $reference);
	}

}
