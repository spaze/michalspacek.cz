<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

final readonly class TalkMetadata
{

	public function __construct(
		private int $id,
		private ?int $slidesTalkId,
		private bool $publishSlides,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getSlidesTalkId(): ?int
	{
		return $this->slidesTalkId;
	}


	public function isPublishSlides(): bool
	{
		return $this->publishSlides;
	}

}
