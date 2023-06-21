<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Translator;
use MichalSpacekCz\DateTime\DateTimeFormatter;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Utils\Html;

class TrainingDateTexyFormatterPlaceholder implements TexyFormatterPlaceholder
{

	public function __construct(
		private readonly Translator $translator,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly DateTimeFormatter $dateTimeFormatter,
	) {
	}


	public static function getPlaceholder(): string
	{
		return 'TRAINING_DATE';
	}


	public function replace(string $placeholder): string
	{
		$upcoming = $this->upcomingTrainingDates->getPublicUpcoming();
		$dates = [];
		if (!isset($upcoming[$placeholder]) || !$upcoming[$placeholder]->getDates()) {
			$dates[] = $this->translator->translate('messages.trainings.nodateyet.short');
		} else {
			foreach ($upcoming[$placeholder]->getDates() as $date) {
				$trainingDate = $date->isTentative()
					? $this->dateTimeFormatter->localeIntervalMonth($date->getStart(), $date->getEnd())
					: $this->dateTimeFormatter->localeIntervalDay($date->getStart(), $date->getEnd());
				$el = Html::el()
					->addHtml(Html::el('strong')->setText($trainingDate))
					->addHtml(Html::el()->setText(' '))
					->addHtml(Html::el()->setText($date->isRemote() ? $this->translator->translate('messages.label.remote') : $date->getVenueCity()));
				$dates[] = $el;
			}
		}
		return sprintf(
			'%s: %s',
			count($dates) > 1 ? $this->translator->translate('messages.trainings.nextdates') : $this->translator->translate('messages.trainings.nextdate'),
			implode(', ', $dates),
		);
	}

}
