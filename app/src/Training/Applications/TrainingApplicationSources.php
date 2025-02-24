<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use Composer\Pcre\Regex;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Training\Resolver\Vrana;
use Nette\Utils\Strings;

final readonly class TrainingApplicationSources
{

	public function __construct(
		private TypedDatabase $database,
		private Vrana $vranaResolver,
	) {
	}


	/**
	 * @return array<string, string> alias => name
	 */
	public function getAll(): array
	{
		return $this->database->fetchPairsStringString(
			'SELECT
				alias,
				name
			FROM
				training_application_sources',
		);
	}


	public function getSourceId(string $source): int
	{
		return $this->database->fetchFieldInt('SELECT id_source FROM training_application_sources WHERE alias = ?', $source);
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
	 */
	public function getSourceNameInitials(string $name): string
	{
		$replace = Regex::replace('/,? s\.r\.o./', '', $name);
		$matches = Regex::matchAll('/(?<=\s|\b)\pL/u', $replace->result);
		return Strings::upper(implode('', $matches->matches[0]));
	}

}
