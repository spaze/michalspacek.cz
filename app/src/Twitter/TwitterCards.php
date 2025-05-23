<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Twitter;

use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\Twitter\Exceptions\TwitterCardNotFoundException;
use Nette\Database\Explorer;
use Nette\Database\Row;

final readonly class TwitterCards
{

	public function __construct(
		private Explorer $database,
		private TypedDatabase $typedDatabase,
	) {
	}


	/**
	 * @return list<TwitterCard>
	 */
	public function getAll(): array
	{
		$cards = [];
		$rows = $this->typedDatabase->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
		foreach ($rows as $row) {
			$cards[] = $this->createFromDatabaseRow($row);
		}
		return $cards;
	}


	/**
	 * @throws TwitterCardNotFoundException
	 */
	public function getCard(string $card): TwitterCard
	{
		$row = $this->database->fetch('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types WHERE card = ?', $card);
		if ($row === null) {
			throw new TwitterCardNotFoundException();
		}
		return $this->createFromDatabaseRow($row);
	}


	public function buildCard(int $id, string $card, string $title): TwitterCard
	{
		return new TwitterCard($id, $card, $title);
	}


	private function createFromDatabaseRow(Row $row): TwitterCard
	{
		assert(is_int($row->cardId));
		assert(is_string($row->card));
		assert(is_string($row->title));

		return $this->buildCard($row->cardId, $row->card, $row->title);
	}

}
