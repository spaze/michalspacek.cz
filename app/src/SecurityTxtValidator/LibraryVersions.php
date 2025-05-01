<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\DateTime\DateTimeFactoryUtc;
use Nette\Database\Explorer;

/**
 * One row per build of the library, a version together with its reference, so a response points at the build that
 * produced it instead of carrying the two strings itself. A build not seen before gets a row, stamped with when.
 */
final readonly class LibraryVersions
{

	public function __construct(
		private Explorer $database,
		private DateTimeFactoryUtc $dateTimeFactory,
	) {
	}


	public function getId(DependencyVersion $version): int
	{
		$this->database->query('INSERT INTO library_versions', [
			'version' => $version->getVersion(),
			'reference' => $version->getReference(),
			'first_seen' => $this->dateTimeFactory->getNow(),
		], 'ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
		return (int)$this->database->getInsertId();
	}

}
