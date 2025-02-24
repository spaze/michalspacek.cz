<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Algorithms;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Pulse\Passwords\Rating;
use Nette\Database\Explorer;

final readonly class PasswordHashingAlgorithms
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
		private Rating $rating,
	) {
	}


	/**
	 * @return list<PasswordHashingAlgorithm>
	 */
	public function getAlgorithms(): array
	{
		$rows = $this->typedDatabase->fetchAll('SELECT id, algo, alias, salted, stretched FROM password_algos ORDER BY algo');
		$algorithms = [];
		foreach ($rows as $row) {
			assert(is_int($row->id));
			assert(is_string($row->algo));
			assert(is_string($row->alias));
			assert(is_int($row->salted));
			assert(is_int($row->stretched));
			$algorithms[] = new PasswordHashingAlgorithm($row->id, $row->algo, $row->alias, (bool)$row->salted, (bool)$row->stretched);
		}
		return $algorithms;
	}


	public function getAlgorithmByName(string $name): ?PasswordHashingAlgorithm
	{
		$row = $this->database->fetch('SELECT id, algo, alias, salted, stretched FROM password_algos WHERE algo = ?', $name);
		if (!$row) {
			return null;
		}
		assert(is_int($row->id));
		assert(is_string($row->algo));
		assert(is_string($row->alias));
		assert(is_int($row->salted));
		assert(is_int($row->stretched));

		return new PasswordHashingAlgorithm($row->id, $row->algo, $row->alias, (bool)$row->salted, (bool)$row->stretched);
	}


	/**
	 * @return int The id of the newly inserted algorithm
	 */
	public function addAlgorithm(string $name, string $alias, bool $salted, bool $stretched): int
	{
		$this->database->query('INSERT INTO password_algos', [
			'algo' => $name,
			'alias' => $alias,
			'salted' => $salted,
			'stretched' => $stretched,
		]);
		return (int)$this->database->getInsertId();
	}


	/**
	 * @return array<string, string> of alias => name
	 */
	public function getSlowHashes(): array
	{
		return $this->typedDatabase->fetchPairsStringString(
			'SELECT alias, algo FROM password_algos WHERE alias IN (?) ORDER BY algo',
			$this->rating->getSlowHashes(),
		);
	}

}
