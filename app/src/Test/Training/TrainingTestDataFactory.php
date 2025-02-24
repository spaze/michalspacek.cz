<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Training;

use DateTime;
use MichalSpacekCz\Training\Applications\TrainingApplication;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatus;
use MichalSpacekCz\Training\ApplicationStatuses\TrainingApplicationStatuses;
use MichalSpacekCz\Training\Files\TrainingFiles;
use MichalSpacekCz\Training\Mails\TrainingMailMessageFactory;
use Nette\Utils\Html;

final readonly class TrainingTestDataFactory
{

	public function __construct(
		private TrainingApplicationStatuses $applicationStatuses,
		private TrainingMailMessageFactory $trainingMailMessageFactory,
		private TrainingFiles $trainingFiles,
	) {
	}


	public function getTrainingApplication(
		int $id,
		?string $name = null,
		?string $email = null,
		bool $familiar = false,
		TrainingApplicationStatus $status = TrainingApplicationStatus::Attended,
		?int $dateId = null,
		string $trainingAction = 'action',
		string $trainingName = 'Training Name',
		?DateTime $trainingStart = null,
		?DateTime $trainingEnd = null,
		bool $remote = false,
		?string $remoteUrl = null,
		?string $feedbackHref = null,
		string $sourceAlias = 'michal-spacek',
	): TrainingApplication {
		return new TrainingApplication(
			$this->applicationStatuses,
			$this->trainingMailMessageFactory,
			$this->trainingFiles,
			$id,
			$name,
			$email,
			$familiar,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			$status,
			new DateTime(),
			true,
			false,
			false,
			$dateId,
			null,
			$trainingAction,
			Html::fromText($trainingName),
			$trainingStart,
			$trainingEnd,
			false,
			$remote,
			$remoteUrl,
			null,
			null,
			$feedbackHref,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			'',
			'',
			null,
			null,
			null,
			'accessToken',
			$sourceAlias,
			'Michal Špaček',
			'MŠ',
		);
	}


	/**
	 * @return array<string, int|string|DateTime|null>
	 */
	public function getDatabaseResultData(int $id): array
	{
		return [
			'id' => $id,
			'name' => null,
			'email' => null,
			'familiar' => 0,
			'company' => null,
			'street' => null,
			'city' => null,
			'zip' => null,
			'country' => null,
			'companyId' => null,
			'companyTaxId' => null,
			'note' => null,
			'status' => 'ATTENDED',
			'statusTime' => new DateTime(),
			'dateId' => null,
			'trainingId' => null,
			'trainingAction' => 'action',
			'trainingName' => 'Le //Name//',
			'trainingStart' => null,
			'trainingEnd' => null,
			'publicDate' => 1,
			'remote' => 1,
			'remoteUrl' => 'https://remote.example/',
			'remoteNotes' => null,
			'videoHref' => null,
			'feedbackHref' => null,
			'venueAction' => null,
			'venueName' => null,
			'venueNameExtended' => null,
			'venueAddress' => null,
			'venueCity' => null,
			'price' => null,
			'vatRate' => null,
			'priceVat' => null,
			'discount' => null,
			'invoiceId' => null,
			'paid' => null,
			'accessToken' => 'token',
			'sourceAlias' => 'michal-spacek',
			'sourceName' => 'Michal Špaček',
		];
	}

}
