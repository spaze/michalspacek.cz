<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Twitter;

final readonly class TwitterCard
{

	public function __construct(
		private int $id,
		private string $card,
		private string $title,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getCard(): string
	{
		return $this->card;
	}


	public function getTitle(): string
	{
		return $this->title;
	}

}
