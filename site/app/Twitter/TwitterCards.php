<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Twitter;

use MichalSpacekCz\Twitter\Exceptions\TwitterCardNotFoundException;
use Nette\Database\Explorer;

class TwitterCards
{

	public function __construct(
		private readonly Explorer $database,
	) {
	}


	/**
	 * @return list<TwitterCard>
	 */
	public function getAll(): array
	{
		$cards = [];
		$rows = $this->database->fetchAll('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types ORDER BY card');
		foreach ($rows as $row) {
			$cards[] = $this->buildCard($row->cardId, $row->card, $row->title);
		}
		return $cards;
	}


	/**
	 * @throws TwitterCardNotFoundException
	 */
	public function getCard(string $card): TwitterCard
	{
		$row = $this->database->fetch('SELECT id_twitter_card_type AS cardId, card, title FROM twitter_card_types WHERE card = ?', $card);
		if (!$row) {
			throw new TwitterCardNotFoundException();
		}
		return $this->buildCard($row->cardId, $row->card, $row->title);
	}


	public function buildCard(int $id, string $card, string $title): TwitterCard
	{
		return new TwitterCard($id, $card, $title);
	}

}
