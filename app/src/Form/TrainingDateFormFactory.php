<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Dates\TrainingDates;
use MichalSpacekCz\Training\Dates\TrainingDatesFormValidator;
use MichalSpacekCz\Training\Dates\TrainingDateStatuses;
use MichalSpacekCz\Training\Trainings\Trainings;
use MichalSpacekCz\Training\Venues\TrainingVenues;
use MichalSpacekCz\Utils\Arrays;
use Nette\Forms\Controls\SubmitButton;
use Nette\Forms\Form;

final readonly class TrainingDateFormFactory
{

	private const string STANDARD = 'Standardní';
	private const string CUSTOM = 'Na zakázku';
	private const string REPLACED = 'Nahrazené';
	private const string DISCONTINUED = 'Ukončené';


	public function __construct(
		private FormFactory $factory,
		private Trainings $trainings,
		private TrainingDates $trainingDates,
		private TrainingDateStatuses $trainingDateStatuses,
		private TrainingDatesFormValidator $trainingDatesFormValidator,
		private TrainingVenues $trainingVenues,
		private TrainingControlsFactory $trainingControlsFactory,
	) {
	}


	/**
	 * @param callable(): void $onSuccessAdd
	 * @param callable(int): void $onSuccessEdit
	 */
	public function create(callable $onSuccessAdd, callable $onSuccessEdit, ?TrainingDate $date = null): UiForm
	{
		$form = $this->factory->create();

		$trainings = [
			self::STANDARD => [],
			self::CUSTOM => [],
			self::REPLACED => [],
			self::DISCONTINUED => [],
		];
		foreach ($this->trainings->getNamesIncludingCustomDiscontinued() as $training) {
			if ($training->getDiscontinuedId() !== null) {
				$key = self::DISCONTINUED;
			} elseif ($training->getSuccessorId() !== null) {
				$key = self::REPLACED;
			} else {
				$key = ($training->isCustom() ? self::CUSTOM : self::STANDARD);
			}
			$trainings[$key][$training->getId()] = $training->getName();
		}
		$form->addSelect('training', 'Školení:', Arrays::filterEmpty($trainings))
			->setRequired('Vyberte prosím školení');

		$venues = [];
		foreach ($this->trainingVenues->getAll() as $venue) {
			$venues[$venue->getId()] = "{$venue->getName()}, {$venue->getCity()}";
		}
		$selectVenue = $form->addSelect('venue', 'Místo:', $venues)
			->setPrompt('- vyberte místo -');

		$start = $form->addText('start', 'Začátek:')
			->setHtmlAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setHtmlAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setRequired('Zadejte prosím začátek')
			->addRule(Form::Pattern, 'Začátek musí být ve formátu YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})');
		$end = $form->addText('end', 'Konec:')
			->setHtmlAttribute('placeholder', 'YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setHtmlAttribute('title', 'Formát YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM')
			->setRequired('Zadejte prosím konec')
			->addRule(Form::Pattern, 'Konec musí být ve formátu YYYY-MM-DD HH:MM nebo DD.MM.YYYY HH:MM', '(\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{2})|(\d{1,2}\.\d{1,2}\.\d{4} \d{1,2}:\d{2})');
		$form->onValidate[] = function () use ($start, $end): void {
			$this->trainingDatesFormValidator->validateFormStartEnd($start, $end);
		};

		$form->addText('label', 'Label:');

		$statuses = [];
		foreach ($this->trainingDateStatuses->getStatuses() as $status) {
			$statuses[$status->id()] = "{$status->value} – {$status->description()}";
		}
		$form->addSelect('status', 'Status:', $statuses)
			->setRequired('Vyberte prosím status');

		$checkboxPublic = $form->addCheckbox('public', 'Veřejné:');
		$checkboxRemote = $form->addCheckbox('remote', 'Online:');
		$checkboxRemote->addConditionOn($selectVenue, Form::Filled)
			->addConditionOn($checkboxPublic, Form::Filled)
			->addRule(Form::Blank, 'Je vybráno místo, veřejné školení nemůže být online');
		$selectVenue->addConditionOn($checkboxRemote, Form::Blank)
			->setRequired('Vyberte prosím místo nebo školení označte jako online');

		$form->addText('remoteUrl', 'Online URL:')
			->addRule(Form::URL, 'Online URL musí být validní URL')
			->addRule(Form::MaxLength, 'Maximální délka URL je %d znaků', 200);

		$format = "Bez HTML značek,\nodřádkování bude v pozvánce zachováno";
		$form->addTextArea('remoteNotes', 'Online poznámky:')
			->setHtmlAttribute('placeholder', $format)
			->setHtmlAttribute('title', $format);

		$form->addSelect('cooperation', 'Spolupráce:', [0 => 'žádná'] + $this->trainings->getCooperations())
			->addRule(Form::Integer);
		$this->trainingControlsFactory->addNote($form);

		$price = $form->addText('price', 'Cena bez DPH:')
			->setHtmlType('number')
			->setHtmlAttribute('title', 'Ponechte prázdné, aby se použila běžná cena');
		$form->onValidate[] = function (UiForm $form) use ($price): void {
			$values = $form->getFormValues();
			assert(is_int($values->training));
			$training = $this->trainings->getById($values->training);
			if ($values->price === '' && $training->getPrice() === null) {
				$price->addError('Běžná cena není nastavena, je třeba nastavit cenu zde');
			}
		};

		$form->addText('studentDiscount', 'Studentská sleva:')
			->setHtmlType('number')
			->setHtmlAttribute('title', 'Ponechte prázdné, aby se použila běžná sleva');

		$form->addText('videoHref', 'Odkaz na záznam:')
			->addRule(Form::URL, 'Odkaz na záznam musí být validní URL')
			->addRule(Form::MaxLength, 'Maximální délka URL je %d znaků', 200);
		$form->addText('feedbackHref', 'Odkaz na feedback formulář:')
			->addRule(Form::URL, 'Odkaz na feedback formulář musí být validní URL')
			->addRule(Form::MaxLength, 'Maximální délka URL je %d znaků', 200);

		$submit = $form->addSubmit('submit', 'Přidat');
		if ($date !== null) {
			$this->setTrainingDate($form, $date, $submit);
		}

		$form->onSuccess[] = function (UiForm $form) use ($onSuccessAdd, $onSuccessEdit, $date): void {
			$values = $form->getFormValues();
			assert(is_int($values->training));
			assert(is_int($values->venue) || $values->venue === null);
			assert(is_bool($values->remote));
			assert(is_string($values->start));
			assert(is_string($values->end));
			assert(is_string($values->label));
			assert(is_int($values->status));
			assert(is_bool($values->public));
			assert(is_int($values->cooperation));
			assert(is_string($values->note));
			assert(is_string($values->price));
			assert(is_string($values->studentDiscount));
			assert(is_string($values->remoteUrl));
			assert(is_string($values->remoteNotes));
			assert(is_string($values->videoHref));
			assert(is_string($values->feedbackHref));
			if ($date !== null) {
				$this->trainingDates->update(
					$date->getId(),
					$values->training,
					$values->venue,
					$values->remote,
					$values->start,
					$values->end,
					$values->label,
					$values->status,
					$values->public,
					$values->cooperation,
					$values->note,
					$values->price === '' ? null : (int)$values->price,
					$values->studentDiscount === '' ? null : (int)$values->studentDiscount,
					$values->remoteUrl,
					$values->remoteNotes,
					$values->videoHref,
					$values->feedbackHref,
				);
			} else {
				$this->trainingDates->add(
					$values->training,
					$values->venue,
					$values->remote,
					$values->start,
					$values->end,
					$values->label,
					$values->status,
					$values->public,
					$values->cooperation,
					$values->note,
					$values->price === '' ? null : (int)$values->price,
					$values->studentDiscount === '' ? null : (int)$values->studentDiscount,
					$values->remoteUrl,
					$values->remoteNotes,
					$values->videoHref,
					$values->feedbackHref,
				);
			}
			$date !== null ? $onSuccessEdit($date->getId()) : $onSuccessAdd();
		};

		return $form;
	}


	public function setTrainingDate(UiForm $form, TrainingDate $date, SubmitButton $submit): void
	{
		$values = [
			'training' => $date->getTrainingId(),
			'venue' => $date->getVenueId(),
			'remote' => $date->isRemote(),
			'remoteUrl' => $date->getRemoteUrl(),
			'remoteNotes' => $date->getRemoteNotes(),
			'start' => $date->getStart()->format('Y-m-d H:i'),
			'end' => $date->getEnd()->format('Y-m-d H:i'),
			'label' => $date->getLabelJson(),
			'status' => $date->getStatus()->id(),
			'public' => $date->isPublic(),
			'cooperation' => $date->getCooperationId(),
			'note' => $date->getNote(),
			'price' => $date->hasCustomPrice() ? $date->getPrice()?->getPrice() : null,
			'studentDiscount' => $date->hasCustomStudentDiscount() ? $date->getStudentDiscount() : null,
			'videoHref' => $date->getVideoHref(),
			'feedbackHref' => $date->getFeedbackHref(),
		];
		$form->setDefaults($values);
		$submit->caption = 'Upravit';
	}

}
