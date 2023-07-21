<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Resolver\Vrana;
use Nette\Database\Explorer;
use Nette\Utils\Strings;

class TrainingApplicationSources
{

	public function __construct(
		private readonly Explorer $database,
		private readonly Vrana $vranaResolver,
	) {
	}


	/**
	 * @return array<string, string> alias => name
	 */
	public function getAll(): array
	{
		return $this->database->fetchPairs(
			'SELECT
				alias,
				name
			FROM
				training_application_sources',
		);
	}


	public function getSourceId(string $source): int
	{
		$id = $this->database->fetchField('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
		if (!is_int($id)) {
			throw new ShouldNotHappenException(sprintf("Source id for source '%s' is a %s not an integer", $source, get_debug_type($id)));
		}
		return $id;
	}


	public function resolveSource(string $note): string
	{
		return $this->vranaResolver->isTrainingApplicationOwner($note) ? 'jakub-vrana' : $this->getDefaultSource();
	}


	public function getDefaultSource(): string
	{
		return 'michal-spacek';
	}


	/**
	 * Shorten source name.
	 *
	 * Removes Czech private limited company designation, if any, and uses only initials from the original name.
	 * Example:
	 *   Michal Špaček -> MŠ
	 *   Internet Info, s.r.o. -> II
	 *
	 * @param string $name
	 * @return string
	 */
	public function getSourceNameInitials(string $name): string
	{
		$name = Strings::replace($name, '/,? s\.r\.o./', '');
		$matches = Strings::matchAll($name, '/(?<=\s|\b)\pL/u', PREG_PATTERN_ORDER);
		return Strings::upper(implode('', current($matches)));
	}

}
