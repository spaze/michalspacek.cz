<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Dates;

use Contributte\Translation\Translator;
use DateTime;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Training\Prices;
use Nette\Database\Row;
use Nette\Utils\Json;

readonly class TrainingDateFactory
{

	private const int LAST_FREE_SEATS_THRESHOLD_DAYS = 7;


	public function __construct(
		private Translator $translator,
		private TexyFormatter $texyFormatter,
		private Prices $prices,
	) {
	}


	public function get(Row $row): TrainingDate
	{
		assert(is_int($row->dateId));
		assert(is_string($row->action));
		assert(is_int($row->trainingId));
		assert($row->start instanceof DateTime);
		assert($row->end instanceof DateTime);
		assert($row->labelJson === null || is_string($row->labelJson));
		assert(is_int($row->public));
		assert(is_string($row->status));
		assert(is_string($row->name));
		assert(is_int($row->remote));
		assert($row->venueId === null || is_int($row->venueId));
		assert($row->venueAction === null || is_string($row->venueAction));
		assert($row->venueHref === null || is_string($row->venueHref));
		assert($row->venueName === null || is_string($row->venueName));
		assert($row->venueNameExtended === null || is_string($row->venueNameExtended));
		assert($row->venueAddress === null || is_string($row->venueAddress));
		assert($row->venueCity === null || is_string($row->venueCity));
		assert($row->venueDescription === null || is_string($row->venueDescription));
		assert($row->note === null || is_string($row->note));
		assert($row->cooperationId === null || is_int($row->cooperationId));
		assert($row->cooperationDescription === null || is_string($row->cooperationDescription));
		assert($row->price === null || is_int($row->price));
		assert(is_int($row->hasCustomPrice));
		assert($row->studentDiscount === null || is_int($row->studentDiscount));
		assert(is_int($row->hasCustomStudentDiscount));
		assert($row->remoteUrl === null || is_string($row->remoteUrl));
		assert($row->remoteNotes === null || is_string($row->remoteNotes));
		assert($row->videoHref === null || is_string($row->videoHref));
		assert($row->feedbackHref === null || is_string($row->feedbackHref));

		$status = TrainingDateStatus::from($row->status);
		return new TrainingDate(
			$row->dateId,
			$row->action,
			$row->trainingId,
			$status === TrainingDateStatus::Tentative,
			$this->lastFreeSeats($row->start, $status),
			$row->start,
			$row->end,
			$this->getLabelFromJson($row->labelJson),
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


	private function getLabelFromJson(?string $json): ?string
	{
		if ($json !== null) {
			$labels = Json::decode($json);
			$label = $labels->{$this->translator->getDefaultLocale()} ?? null;
			if (!is_string($label)) {
				return null;
			}
		}
		return $label ?? null;
	}

}
