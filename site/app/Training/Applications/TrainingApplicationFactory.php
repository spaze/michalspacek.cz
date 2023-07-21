<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Applications;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Price;
use MichalSpacekCz\Training\Statuses;
use MichalSpacekCz\Training\TrainingMailMessageFactory;
use Nette\Database\Row;
use ParagonIE\Halite\Alerts\HaliteAlert;
use SodiumException;
use Spaze\Encryption\Symmetric\StaticKey;

class TrainingApplicationFactory
{

	public function __construct(
		private readonly Statuses $trainingStatuses,
		private readonly TrainingMailMessageFactory $trainingMailMessageFactory,
		private readonly StaticKey $emailEncryption,
		private readonly TexyFormatter $texyFormatter,
		private readonly TrainingApplicationSources $trainingApplicationSources,
		private readonly TrainingFiles $trainingFiles,
	) {
	}


	/**
	 * @throws SodiumException
	 * @throws HaliteAlert
	 */
	public function createFromDatabaseRow(Row $row): TrainingApplication
	{
		$price = new Price($row->price, $row->discount, $row->vatRate, $row->priceVat);
		return new TrainingApplication(
			$this->trainingStatuses,
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
			$row->status,
			$row->statusTime,
			in_array($row->status, $this->trainingStatuses->getAttendedStatuses(), true),
			in_array($row->status, $this->trainingStatuses->getDiscardedStatuses(), true),
			in_array($row->status, $this->trainingStatuses->getAllowFilesStatuses(), true),
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
