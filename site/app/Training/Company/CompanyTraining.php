<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Company;

use Nette\Utils\Html;

class CompanyTraining
{

	public function __construct(
		private readonly int $id,
		private readonly string $action,
		private readonly Html $name,
		private readonly ?Html $description,
		private readonly Html $content,
		private readonly ?Html $upsell,
		private readonly ?Html $prerequisites,
		private readonly ?Html $audience,
		private readonly ?int $capacity,
		private readonly ?int $price,
		private readonly ?int $alternativeDurationPrice,
		private readonly ?int $studentDiscount,
		private readonly ?Html $materials,
		private readonly bool $custom,
		private readonly Html $duration,
		private readonly Html $alternativeDuration,
		private readonly ?Html $alternativeDurationPriceText,
		private readonly ?int $successorId,
		private readonly ?int $discontinuedId,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getAction(): string
	{
		return $this->action;
	}


	public function getName(): Html
	{
		return $this->name;
	}


	public function getDescription(): ?Html
	{
		return $this->description;
	}


	public function getContent(): Html
	{
		return $this->content;
	}


	public function getUpsell(): ?Html
	{
		return $this->upsell;
	}


	public function getPrerequisites(): ?Html
	{
		return $this->prerequisites;
	}


	public function getAudience(): ?Html
	{
		return $this->audience;
	}


	public function getCapacity(): ?int
	{
		return $this->capacity;
	}


	public function getPrice(): ?int
	{
		return $this->price;
	}


	public function getAlternativeDurationPrice(): ?int
	{
		return $this->alternativeDurationPrice;
	}


	public function getStudentDiscount(): ?int
	{
		return $this->studentDiscount;
	}


	public function getMaterials(): ?Html
	{
		return $this->materials;
	}


	public function isCustom(): bool
	{
		return $this->custom;
	}


	public function getDuration(): Html
	{
		return $this->duration;
	}


	public function getAlternativeDuration(): Html
	{
		return $this->alternativeDuration;
	}


	public function getAlternativeDurationPriceText(): ?Html
	{
		return $this->alternativeDurationPriceText;
	}


	public function getSuccessorId(): ?int
	{
		return $this->successorId;
	}


	public function getDiscontinuedId(): ?int
	{
		return $this->discontinuedId;
	}

}
