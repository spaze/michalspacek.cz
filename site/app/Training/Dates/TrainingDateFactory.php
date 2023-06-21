<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Prices;
use Nette\Database\Row;
use Nette\Utils\Json;

class TrainingDateFactory
{

	private const LAST_FREE_SEATS_THRESHOLD_DAYS = 7;


	public function __construct(
		private readonly Translator $translator,
		private readonly TexyFormatter $texyFormatter,
		private readonly Prices $prices,
	) {
	}


	public function get(Row $row): TrainingDate
	{
		$status = TrainingDateStatus::from($row->status);
		return new TrainingDate(
			$row->dateId,
			$row->action,
			$row->trainingId,
			$status === TrainingDateStatus::Tentative,
			$this->lastFreeSeats($row->start, $status),
			$row->start,
			$row->end,
			$row->labelJson ? Json::decode($row->labelJson)->{$this->translator->getDefaultLocale()} : null,
			$row->labelJson,
			(bool)$row->public,
			$status,
			$this->translator->translate($row->name),
			(bool)$row->remote,
			$row->venueId,
			$row->venueAction,
			$row->venueHref,
			$row->venueName,
			$row->venueNameExtended,
			$row->venueAddress,
			$row->venueCity,
			$row->venueDescription ? $this->texyFormatter->format($row->venueDescription) : null,
			$row->note,
			$row->cooperationId,
			$row->cooperationDescription ? $this->texyFormatter->format($row->cooperationDescription) : null,
			$row->price ? $this->prices->resolvePriceVat($row->price) : null,
			(bool)$row->hasCustomPrice,
			$row->studentDiscount,
			(bool)$row->hasCustomStudentDiscount,
			$row->remoteUrl,
			$row->remoteNotes,
			$row->videoHref,
			$row->feedbackHref,
		);
	}


	private function lastFreeSeats(DateTime $start, TrainingDateStatus $status): bool
	{
		$now = new DateTime();
		return ($start->diff($now)->days <= self::LAST_FREE_SEATS_THRESHOLD_DAYS && $start > $now && $status !== TrainingDateStatus::Tentative);
	}

}
