<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Trainings;

use Nette\Utils\Html;

readonly class Training
{

	public function __construct(
		private int $id,
		private string $action,
		private Html $name,
		private Html $description,
		private Html $content,
		private ?Html $upsell,
		private ?Html $prerequisites,
		private ?Html $audience,
		private ?int $capacity,
		private ?int $price,
		private ?int $studentDiscount,
		private ?Html $materials,
		private bool $custom,
		private ?int $successorId,
		private ?int $discontinuedId,
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


	public function getDescription(): Html
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


	public function getSuccessorId(): ?int
	{
		return $this->successorId;
	}


	public function getDiscontinuedId(): ?int
	{
		return $this->discontinuedId;
	}

}
