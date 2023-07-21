<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Reviews;

use Nette\Utils\Html;

class TrainingReview
{

	/**
	 * @param positive-int|null $ranking
	 */
	public function __construct(
		private readonly int $id,
		private readonly string $name,
		private readonly string $company,
		private readonly ?string $jobTitle,
		private readonly Html $review,
		private readonly string $reviewTexy,
		private readonly ?string $href,
		private readonly bool $hidden,
		private readonly ?int $ranking,
		private readonly ?string $note,
		private readonly int $dateId,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getCompany(): string
	{
		return $this->company;
	}


	public function getJobTitle(): ?string
	{
		return $this->jobTitle;
	}


	public function getReview(): Html
	{
		return $this->review;
	}


	public function getReviewTexy(): string
	{
		return $this->reviewTexy;
	}


	public function getHref(): ?string
	{
		return $this->href;
	}


	public function isHidden(): bool
	{
		return $this->hidden;
	}


	/**
	 * @return positive-int|null
	 */
	public function getRanking(): ?int
	{
		return $this->ranking;
	}


	public function getNote(): ?string
	{
		return $this->note;
	}


	public function getDateId(): int
	{
		return $this->dateId;
	}

}
