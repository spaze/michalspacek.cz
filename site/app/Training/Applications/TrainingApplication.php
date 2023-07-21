<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Training\Files\TrainingFile;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Files\TrainingFilesCollection;
use MichalSpacekCz\Training\MailMessageAdmin;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\TrainingMailMessageFactory;
use Nette\Utils\Html;

class TrainingApplication
{

	private ?string $nextStatus = null;

	/** @var array<int, string>|null */
	private ?array $childrenStatuses = null;

	/** @var TrainingFilesCollection<int, TrainingFile>|null */
	private ?TrainingFilesCollection $files = null;


	public function __construct(
		private readonly Statuses $trainingStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly TrainingFiles $trainingFiles,
		private readonly int $id,
		private readonly ?string $name,
		private readonly ?string $email,
		private readonly bool $familiar,
		private readonly ?string $company,
		private readonly ?string $street,
		private readonly ?string $city,
		private readonly ?string $zip,
		private readonly ?string $country,
		private readonly ?string $companyId,
		private readonly ?string $companyTaxId,
		private readonly ?string $note,
		private readonly string $status,
		private readonly DateTime $statusTime,
		private readonly bool $attended,
		private readonly bool $discarded,
		private readonly bool $allowFiles,
		private readonly ?int $dateId,
		private readonly ?int $trainingId,
		private readonly string $trainingAction,
		private readonly Html $trainingName,
		private readonly ?DateTime $trainingStart,
		private readonly ?DateTime $trainingEnd,
		private readonly bool $publicDate,
		private readonly bool $remote,
		private readonly ?string $remoteUrl,
		private readonly ?string $remoteNotes,
		private readonly ?string $videoHref,
		private readonly ?string $feedbackHref,
		private readonly ?string $venueAction,
		private readonly ?string $venueName,
		private readonly ?string $venueNameExtended,
		private readonly ?string $venueAddress,
		private readonly ?string $venueCity,
		private readonly ?float $price,
		private readonly ?float $vatRate,
		private readonly ?float $priceVat,
		private readonly string $priceWithCurrency,
		private readonly string $priceVatWithCurrency,
		private readonly ?float $discount,
		private ?int $invoiceId,
		private readonly ?DateTime $paid,
		private readonly string $accessToken,
		private readonly string $sourceAlias,
		private readonly string $sourceName,
		private readonly string $sourceNameInitials,
	) {
	}


	public function getId(): int
	{
		return $this->id;
	}


	public function getName(): ?string
	{
		return $this->name;
	}


	public function getEmail(): ?string
	{
		return $this->email;
	}


	public function isFamiliar(): bool
	{
		return $this->familiar;
	}


	public function getCompany(): ?string
	{
		return $this->company;
	}


	public function getStreet(): ?string
	{
		return $this->street;
	}


	public function getCity(): ?string
	{
		return $this->city;
	}


	public function getZip(): ?string
	{
		return $this->zip;
	}


	public function getCountry(): ?string
	{
		return $this->country;
	}


	public function getCompanyId(): ?string
	{
		return $this->companyId;
	}


	public function getCompanyTaxId(): ?string
	{
		return $this->companyTaxId;
	}


	public function getNote(): ?string
	{
		return $this->note;
	}


	public function getStatus(): string
	{
		return $this->status;
	}


	public function getStatusTime(): DateTime
	{
		return $this->statusTime;
	}


	public function getNextStatus(): ?string
	{
		return $this->nextStatus;
	}


	public function setNextStatus(?string $nextStatus): void
	{
		$this->nextStatus = $nextStatus;
	}


	public function isAttended(): bool
	{
		return $this->attended;
	}


	public function isDiscarded(): bool
	{
		return $this->discarded;
	}


	public function isAllowFiles(): bool
	{
		return $this->allowFiles;
	}


	public function getDateId(): ?int
	{
		return $this->dateId;
	}


	public function getTrainingId(): ?int
	{
		return $this->trainingId;
	}


	public function getTrainingAction(): string
	{
		return $this->trainingAction;
	}


	public function getTrainingName(): Html
	{
		return $this->trainingName;
	}


	public function getTrainingStart(): ?DateTime
	{
		return $this->trainingStart;
	}


	public function getTrainingEnd(): ?DateTime
	{
		return $this->trainingEnd;
	}


	public function isPublicDate(): bool
	{
		return $this->publicDate;
	}


	public function isRemote(): bool
	{
		return $this->remote;
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


	public function getVenueAction(): ?string
	{
		return $this->venueAction;
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


	public function getPrice(): ?float
	{
		return $this->price;
	}


	public function getVatRate(): ?float
	{
		return $this->vatRate;
	}


	public function getPriceVat(): ?float
	{
		return $this->priceVat;
	}


	public function getPriceWithCurrency(): string
	{
		return $this->priceWithCurrency;
	}


	public function getPriceVatWithCurrency(): string
	{
		return $this->priceVatWithCurrency;
	}


	public function getDiscount(): ?float
	{
		return $this->discount;
	}


	public function getInvoiceId(): ?int
	{
		return $this->invoiceId;
	}


	public function setInvoiceId(int $invoiceId): void
	{
		$this->invoiceId = $invoiceId;
	}


	public function getPaid(): ?DateTime
	{
		return $this->paid;
	}


	public function getAccessToken(): string
	{
		return $this->accessToken;
	}


	public function getSourceAlias(): string
	{
		return $this->sourceAlias;
	}


	public function getSourceName(): string
	{
		return $this->sourceName;
	}


	public function getSourceNameInitials(): string
	{
		return $this->sourceNameInitials;
	}


	public function getMailMessage(): MailMessageAdmin
	{
		return $this->trainingMailMessageFactory->getMailMessage($this);
	}


	/**
	 * @return array<int, string>
	 */
	public function getChildrenStatuses(): array
	{
		if ($this->childrenStatuses === null) {
			$this->childrenStatuses = $this->trainingStatuses->getChildrenStatusesForApplicationId($this->getStatus(), $this->getId());
		}
		return $this->childrenStatuses;
	}


	/**
	 * @return TrainingFilesCollection<int, TrainingFile>
	 */
	public function getFiles(): TrainingFilesCollection
	{
		if ($this->files === null) {
			$this->files = $this->trainingFiles->getFiles($this);
		}
		return $this->files;
	}

}
