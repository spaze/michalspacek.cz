<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Twitter;

class TwitterCard
{

	public function __construct(
		private readonly int $id,
		private readonly string $card,
		private readonly string $title,
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
