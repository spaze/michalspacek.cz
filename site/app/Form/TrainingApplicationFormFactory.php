<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\ApplicationForm\TrainingApplicationFormSuccess;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDates;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextInput;
use Nette\Utils\Html;

class TrainingApplicationFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Translator $translator,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly TrainingDates $trainingDates,
		private readonly TrainingApplicationFormSuccess $formSuccess,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param array<int, TrainingDate> $dates
	 */
	public function create(
		callable $onSuccess,
		callable $onError,
		string $action,
		Html $name,
		array $dates,
		TrainingApplicationSessionSection $sessionSection,
	): UiForm {
		$form = $this->factory->create();

		$inputDates = [];
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$el = Html::el()->setText($this->trainingDates->formatDateVenueForUser($date));
			if ($date->getLabel()) {
				if ($multipleDates) {
					$el->addText(" [{$date->getLabel()}]");
				} else {
					$el->addHtml(Html::el('small', ['class' => 'label'])->setText($date->getLabel()));
				}
			}
			$inputDates[$date->getId()] = $el;
		}

		// trainingId is actually dateId, oh well
		if ($multipleDates) {
			$form->addSelect('trainingId', $this->translator->translate('label.trainingdate'), $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -')
				->addRule($form::Integer);
		}

		$this->trainingControlsFactory->addAttendee($form);
		$this->trainingControlsFactory->addCompany($form);
		$this->trainingControlsFactory->addNote($form)
			->setHtmlAttribute('placeholder', $this->translator->translate('messages.trainings.applicationform.note'));
		$country = $this->trainingControlsFactory->addCountry($form);

		$form->addSubmit('signUp', 'Odeslat');

		$form->onSuccess[] = function (UiForm $form) use ($onSuccess, $onError, $action, $name, $dates, $multipleDates, $sessionSection): void {
			$this->formSuccess->success($form, $onSuccess, $onError, $action, $name, $dates, $multipleDates, $sessionSection);
		};
		$this->setApplication($form, $sessionSection, $country);
		return $form;
	}


	private function setApplication(UiForm $form, TrainingApplicationSessionSection $application, SelectBox $country): void
	{
		$form->setDefaults($application->getApplicationValues());
		$message = "messages.label.taxid.{$country->getValue()}";
		$caption = $this->translator->translate($message);
		if ($caption !== $message) {
			$input = $form->getComponent('companyTaxId');
			if (!$input instanceof TextInput) {
				throw new ShouldNotHappenException(sprintf("The 'companyTaxId' component should be '%s' but it's a %s", TextInput::class, get_debug_type($input)));
			}
			$input->caption = "{$caption}:";
		}
	}

}
