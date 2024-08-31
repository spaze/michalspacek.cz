<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use MichalSpacekCz\Training\Price;
use Nette\Database\Row;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;
use Spaze\Encryption\SymmetricKeyEncryption;

readonly class TrainingApplicationFactory
{

	public function __construct(
		private TrainingApplicationStatuses $trainingApplicationStatuses,
		private TrainingMailMessageFactory $trainingMailMessageFactory,
		private SymmetricKeyEncryption $emailEncryption,
		private TexyFormatter $texyFormatter,
		private TrainingApplicationSources $trainingApplicationSources,
		private TrainingFiles $trainingFiles,
	) {
	}


	/**
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function createFromDatabaseRow(Row $row): TrainingApplication
	{
		assert(is_int($row->id));
		assert($row->name === null || is_string($row->name));
		assert($row->email === null || is_string($row->email));
		assert(is_int($row->familiar));
		assert($row->company === null || is_string($row->company));
		assert($row->street === null || is_string($row->street));
		assert($row->city === null || is_string($row->city));
		assert($row->zip === null || is_string($row->zip));
		assert($row->country === null || is_string($row->country));
		assert($row->companyId === null || is_string($row->companyId));
		assert($row->companyTaxId === null || is_string($row->companyTaxId));
		assert($row->note === null || is_string($row->note));
		assert(is_string($row->status));
		assert($row->statusTime instanceof DateTime);
		assert($row->dateId === null || is_int($row->dateId));
		assert($row->trainingId === null || is_int($row->trainingId));
		assert(is_string($row->trainingAction));
		assert(is_string($row->trainingName));
		assert($row->trainingStart === null || $row->trainingStart instanceof DateTime);
		assert($row->trainingEnd === null || $row->trainingEnd instanceof DateTime);
		assert(is_int($row->publicDate));
		assert(is_int($row->remote));
		assert($row->remoteUrl === null || is_string($row->remoteUrl));
		assert($row->remoteNotes === null || is_string($row->remoteNotes));
		assert($row->videoHref === null || is_string($row->videoHref));
		assert($row->feedbackHref === null || is_string($row->feedbackHref));
		assert($row->venueAction === null || is_string($row->venueAction));
		assert($row->venueName === null || is_string($row->venueName));
		assert($row->venueNameExtended === null || is_string($row->venueNameExtended));
		assert($row->venueAddress === null || is_string($row->venueAddress));
		assert($row->venueCity === null || is_string($row->venueCity));
		assert($row->price === null || is_float($row->price));
		assert($row->vatRate === null || is_float($row->vatRate));
		assert($row->priceVat === null || is_float($row->priceVat));
		assert($row->discount === null || is_int($row->discount));
		assert($row->invoiceId === null || is_int($row->invoiceId));
		assert($row->paid === null || $row->paid instanceof DateTime);
		assert(is_string($row->accessToken));
		assert(is_string($row->sourceAlias));
		assert(is_string($row->sourceName));

		$price = new Price($row->price, $row->discount, $row->vatRate, $row->priceVat);
		$status = TrainingApplicationStatus::from($row->status);
		return new TrainingApplication(
			$this->trainingApplicationStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			$row->id,
			$row->name,
			$row->email ? $this->emailEncryption->decrypt($row->email) : null,
			(bool)$row->familiar,
			$row->company,
			$row->street,
			$row->city,
			$row->zip,
			$row->country,
			$row->companyId,
			$row->companyTaxId,
			$row->note,
			$status,
			$row->statusTime,
			in_array($status, $this->trainingApplicationStatuses->getAttendedStatuses(), true),
			in_array($status, $this->trainingApplicationStatuses->getDiscardedStatuses(), true),
			in_array($status, $this->trainingApplicationStatuses->getAllowFilesStatuses(), true),
			$row->dateId,
			$row->trainingId,
			$row->trainingAction,
			$this->texyFormatter->translate($row->trainingName),
			$row->trainingStart,
			$row->trainingEnd,
			(bool)$row->publicDate,
			(bool)$row->remote,
			$row->remoteUrl,
			$row->remoteNotes,
			$row->videoHref,
			$row->feedbackHref,
			$row->venueAction,
			$row->venueName,
			$row->venueNameExtended,
			$row->venueAddress,
			$row->venueCity,
			$row->price,
			$row->vatRate,
			$row->priceVat,
			$price->getPriceWithCurrency(),
			$price->getPriceVatWithCurrency(),
			$row->discount,
			$row->invoiceId,
			$row->paid,
			$row->accessToken,
			$row->sourceAlias,
			$row->sourceName,
			$this->trainingApplicationSources->getSourceNameInitials($row->sourceName),
		);
	}

}
