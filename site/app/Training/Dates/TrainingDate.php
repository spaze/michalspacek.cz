<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use DateTime;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\Price;
use Nette\Utils\Html;

class TrainingDate
{

	/** @var list<TrainingApplication> */
	private array $applications = [];

	/** @var list<TrainingApplication> */
	private array $canceledApplications = [];


	public function __construct(
		private readonly int $id,
		private readonly string $action,
		private readonly int $trainingId,
		private readonly bool $tentative,
		private readonly bool $lastFreeSeats,
		private readonly DateTime $start,
		private readonly DateTime $end,
		private readonly ?string $label,
		private readonly ?string $labelJson,
		private readonly bool $public,
		private readonly TrainingDateStatus $status,
		private readonly string $name,
		private readonly bool $remote,
		private readonly ?int $venueId,
		private readonly ?string $venueAction,
		private readonly ?string $venueHref,
		private readonly ?string $venueName,
		private readonly ?string $venueNameExtended,
		private readonly ?string $venueAddress,
		private readonly ?string $venueCity,
		private readonly ?Html $venueDescription,
		private readonly ?string $note,
		private readonly ?int $cooperationId,
		private readonly ?Html $cooperationDescription,
		private readonly ?Price $price,
		private readonly bool $hasCustomPrice,
		private readonly ?int $studentDiscount,
		private readonly bool $hasCustomStudentDiscount,
		private readonly ?string $remoteUrl,
		private readonly ?string $remoteNotes,
		private readonly ?string $videoHref,
		private readonly ?string $feedbackHref,
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


	public function getTrainingId(): int
	{
		return $this->trainingId;
	}


	public function isTentative(): bool
	{
		return $this->tentative;
	}


	public function isLastFreeSeats(): bool
	{
		return $this->lastFreeSeats;
	}


	public function getStart(): DateTime
	{
		return $this->start;
	}


	public function getEnd(): DateTime
	{
		return $this->end;
	}


	public function getLabel(): ?string
	{
		return $this->label;
	}


	public function getLabelJson(): ?string
	{
		return $this->labelJson;
	}


	public function isPublic(): bool
	{
		return $this->public;
	}


	public function getStatus(): TrainingDateStatus
	{
		return $this->status;
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function isRemote(): bool
	{
		return $this->remote;
	}


	public function getVenueId(): ?int
	{
		return $this->venueId;
	}


	public function getVenueAction(): ?string
	{
		return $this->venueAction;
	}


	public function getVenueHref(): ?string
	{
		return $this->venueHref;
	}


	public function getVenueName(): ?string
	{
		return $this->venueName;
	}


	public function getVenueNameExtended(): ?string
	{
		return $this->venueNameExtended;
	}


	public function getVenueAddress(): ?string
	{
		return $this->venueAddress;
	}


	public function getVenueCity(): ?string
	{
		return $this->venueCity;
	}


	public function getVenueDescription(): ?Html
	{
		return $this->venueDescription;
	}


	public function getNote(): ?string
	{
		return $this->note;
	}


	public function getCooperationId(): ?int
	{
		return $this->cooperationId;
	}


	public function getCooperationDescription(): ?Html
	{
		return $this->cooperationDescription;
	}


	public function getPrice(): ?Price
	{
		return $this->price;
	}


	public function hasCustomPrice(): bool
	{
		return $this->hasCustomPrice;
	}


	public function getStudentDiscount(): ?int
	{
		return $this->studentDiscount;
	}


	public function hasCustomStudentDiscount(): bool
	{
		return $this->hasCustomStudentDiscount;
	}


	public function getRemoteUrl(): ?string
	{
		return $this->remoteUrl;
	}


	public function getRemoteNotes(): ?string
	{
		return $this->remoteNotes;
	}


	public function getVideoHref(): ?string
	{
		return $this->videoHref;
	}


	public function getFeedbackHref(): ?string
	{
		return $this->feedbackHref;
	}


	/**
	 * @param list<TrainingApplication> $applications
	 */
	public function setApplications(array $applications): void
	{
		$this->applications = $applications;
	}


	/**
	 * @return list<TrainingApplication>
	 */
	public function getApplications(): array
	{
		return $this->applications;
	}


	/**
	 * @param list<TrainingApplication> $applications
	 */
	public function setCanceledApplications(array $applications): void
	{
		$this->canceledApplications = $applications;
	}


	/**
	 * @return list<TrainingApplication>
	 */
	public function getCanceledApplications(): array
	{
		return $this->canceledApplications;
	}


	public function getValidApplicationsCount(): int
	{
		return count($this->applications);
	}


	public function isAttentionRequired(): bool
	{
		return $this->canceledApplications || $this->getStatus() === TrainingDateStatus::Canceled && $this->getValidApplicationsCount();
	}

}
