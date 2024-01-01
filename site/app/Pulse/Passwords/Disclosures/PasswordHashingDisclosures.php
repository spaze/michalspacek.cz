<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Disclosures;

use DateTime;
use MichalSpacekCz\Pulse\Passwords\Rating;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Database\Explorer;

readonly class PasswordHashingDisclosures
{

	public function __construct(
		private Explorer $database,
		private Rating $rating,
	) {
	}


	/**
	 * @return list<PasswordHashingDisclosureType>
	 */
	public function getDisclosureTypes(): array
	{
		$rows = $this->database->fetchAll('SELECT id, alias, type FROM password_disclosure_types ORDER BY type');
		$types = [];
		foreach ($rows as $row) {
			$types[] = new PasswordHashingDisclosureType($row->id, $row->alias, $row->type);
		}
		return $types;
	}


	/**
	 * @return array<string, string> of alias => name
	 */
	public function getVisibleDisclosures(): array
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getVisibleDisclosures(),
		);
	}


	/**
	 * @return array<string, string> of alias => name
	 */
	public function getInvisibleDisclosures(): array
	{
		return $this->database->fetchPairs(
			'SELECT alias, type FROM password_disclosure_types WHERE alias IN (?) ORDER BY type',
			$this->rating->getInvisibleDisclosures(),
		);
	}


	public function getDisclosureId(string $url, string $archive): ?int
	{
		$id = $this->database->fetchField('SELECT id FROM password_disclosures WHERE url = ? AND archive = ?', $url, $archive);
		if (!$id) {
			return null;
		} elseif (!is_int($id)) {
			throw new ShouldNotHappenException(sprintf("Disclosure id for URL '%s' and archive '%s' is a %s not an integer", $url, $archive, get_debug_type($id)));
		}
		return $id;
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
			'published' => (empty($published) ? null : new DateTime($published)),
			'added' => new DateTime(),
		]);
		return (int)$this->database->getInsertId();
	}

}
