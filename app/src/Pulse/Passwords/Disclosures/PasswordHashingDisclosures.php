<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Disclosures;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeFactory;
use MichalSpacekCz\Pulse\Passwords\Rating;
use Nette\Database\Explorer;

final readonly class PasswordHashingDisclosures
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private Rating $rating,
		private DateTimeFactory $dateTimeFactory,
	) {
	}


	/**
	 * @return list<PasswordHashingDisclosureType>
	 */
	public function getDisclosureTypes(): array
	{
		$rows = $this->typedDatabase->fetchAll('SELECT id, alias, type FROM password_disclosure_types ORDER BY type');
		$types = [];
		foreach ($rows as $row) {
			assert(is_int($row->id));
			assert(is_string($row->alias));
			assert(is_string($row->type));
			$types[] = new PasswordHashingDisclosureType($row->id, $row->alias, $row->type);
		}
		return $types;
	}


	/**
	 * @return array<string, string> of alias => name
	 */
	public function getVisibleDisclosures(): array
	{
		return $this->typedDatabase->fetchPairsStringString(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getVisibleDisclosures(),
		);
	}


	/**
	 * @return array<string, string> of alias => name
	 */
	public function getInvisibleDisclosures(): array
	{
		return $this->typedDatabase->fetchPairsStringString(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getInvisibleDisclosures(),
		);
	}


	public function getDisclosureId(string $url, string $archive): ?int
	{
		return $this->typedDatabase->fetchFieldIntNullable('SELECT id FROM password_disclosures WHERE url = ? AND archive = ?', $url, $archive);
	}


	/**
	 * @return int The id of the newly inserted disclosure
	 */
	public function addDisclosure(int $type, string $url, string $archive, string $note, string $published): int
	{
		$this->database->query('INSERT INTO password_disclosures', [
			'key_password_disclosure_types' => $type,
			'url' => $url,
			'archive' => $archive,
			'note' => (empty($note) ? null : $note),
			'published' => (empty($published) ? null : $this->dateTimeFactory->create($published)),
			'added' => $this->dateTimeFactory->create(),
		]);
		return (int)$this->database->getInsertId();
	}

}
