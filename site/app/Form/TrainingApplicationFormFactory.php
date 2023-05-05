<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\Controls\TrainingControlsFactory;
use MichalSpacekCz\Training\Applications;
use MichalSpacekCz\Training\Dates;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotAvailableException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotUpcomingException;
use MichalSpacekCz\Training\FormDataLogger;
use MichalSpacekCz\Training\FormSpam;
use MichalSpacekCz\Training\Mails;
use Nette\Application\UI\Form;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Row;
use Nette\Forms\Controls\TextInput;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use PDOException;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationFormFactory
{

	public function __construct(
		private readonly FormFactory $factory,
		private readonly Translator $translator,
		private readonly TrainingControlsFactory $trainingControlsFactory,
		private readonly Dates $trainingDates,
		private readonly FormDataLogger $formDataLogger,
		private readonly FormSpam $formSpam,
		private readonly Applications $trainingApplications,
		private readonly Mails $trainingMails,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param callable(): Template $createTemplate
	 * @param string $action
	 * @param Html $name
	 * @param Row[] $dates
	 * @param SessionSection<string> $sessionSection
	 * @return Form
	 */
	public function create(
		callable $onSuccess,
		callable $onError,
		callable $createTemplate,
		string $action,
		Html $name,
		array $dates,
		SessionSection $sessionSection,
	): Form {
		$form = $this->factory->create();

		$inputDates = [];
		$multipleDates = count($dates) > 1;
		foreach ($dates as $date) {
			$el = Html::el()->setText($this->trainingDates->formatDateVenueForUser($date));
			if ($date->label) {
				if ($multipleDates) {
					$el->addText(" [{$date->label}]");
				} else {
					$el->addHtml(Html::el('small', ['class' => 'label'])->setText($date->label));
				}
			}
			$inputDates[$date->dateId] = $el;
		}

		// trainingId is actually dateId, oh well
		if ($multipleDates) {
			$form->addSelect('trainingId', $this->translator->translate('label.trainingdate'), $inputDates)
				->setRequired('Vyberte prosím termín a místo školení')
				->setPrompt('- vyberte termín a místo -')
				->addRule($form::INTEGER);
		}

		$this->trainingControlsFactory->addAttendee($form);
		$this->trainingControlsFactory->addCompany($form);
		$this->trainingControlsFactory->addNote($form)
			->setHtmlAttribute('placeholder', $this->translator->translate('messages.trainings.applicationform.note'));
		$this->trainingControlsFactory->addCountry($form);

		$form->addSubmit('signUp', 'Odeslat');

		$form->onSuccess[] = function (Form $form, stdClass $values) use ($onSuccess, $onError, $createTemplate, $action, $name, $dates, $multipleDates, $sessionSection): void {
			try {
				$this->formSpam->check($values, $action, $sessionSection);
				if ($multipleDates) {
					$this->checkTrainingDate($values, $action, $dates, $sessionSection);
					$date = $dates[$values->trainingId] ?? false;
				} else {
					$date = reset($dates);
				}
				if (!$date) {
					throw new TrainingDateNotAvailableException();
				}

				if ($date->tentative) {
					$this->trainingApplications->addInvitation(
						$date,
						$values->name,
						$values->email,
						$values->company,
						$values->street,
						$values->city,
						$values->zip,
						$values->country,
						$values->companyId,
						$values->companyTaxId,
						$values->note,
					);
				} else {
					if (isset($sessionSection->application[$action]) && $sessionSection->application[$action]['dateId'] == $values->trainingId) {
						$applicationId = $this->trainingApplications->updateApplication(
							$date,
							$sessionSection->application[$action]['id'],
							$values->name,
							$values->email,
							$values->company,
							$values->street,
							$values->city,
							$values->zip,
							$values->country,
							$values->companyId,
							$values->companyTaxId,
							$values->note,
						);
						$sessionSection->application[$action] = null;
					} else {
						$applicationId = $this->trainingApplications->addApplication(
							$date,
							$values->name,
							$values->email,
							$values->company,
							$values->street,
							$values->city,
							$values->zip,
							$values->country,
							$values->companyId,
							$values->companyTaxId,
							$values->note,
						);
					}
					$this->trainingMails->sendSignUpMail(
						$applicationId,
						$createTemplate(),
						$values->email,
						$values->name,
						$date->start,
						$date->end,
						$action,
						$name,
						$date->remote,
						$date->venueName,
						$date->venueNameExtended,
						$date->venueAddress,
						$date->venueCity,
					);
				}
				$sessionSection->trainingId = $date->dateId;
				$sessionSection->name = $values->name;
				$sessionSection->email = $values->email;
				$sessionSection->company = $values->company;
				$sessionSection->street = $values->street;
				$sessionSection->city = $values->city;
				$sessionSection->zip = $values->zip;
				$sessionSection->country = $values->country;
				$sessionSection->companyId = $values->companyId;
				$sessionSection->companyTaxId = $values->companyTaxId;
				$sessionSection->note = $values->note;
				$onSuccess($action);
			} catch (SpammyApplicationException $e) {
				Debugger::log($e);
				$onError('messages.trainings.spammyapplication');
			} catch (TrainingDateNotUpcomingException) {
				$onError('messages.trainings.wrongdateapplication');
			} catch (TrainingDateNotAvailableException $e) {
				Debugger::log($e);
				$onError('messages.trainings.wrongdateapplication');
			} catch (PDOException $e) {
				Debugger::log($e, Debugger::ERROR);
				$onError('messages.trainings.errorapplication');
			}
		};
		$this->setApplication($form, $sessionSection);
		return $form;
	}


	/**
	 * @param Form $form
	 * @param SessionSection<string> $application
	 */
	private function setApplication(Form $form, SessionSection $application): void
	{
		$values = [
			'name' => $application->name,
			'email' => $application->email,
			'company' => $application->company,
			'street' => $application->street,
			'city' => $application->city,
			'zip' => $application->zip,
			'country' => $application->country,
			'companyId' => $application->companyId,
			'companyTaxId' => $application->companyTaxId,
			'note' => $application->note,
		];
		$form->setDefaults($values);

		if (!empty($application->country)) {
			$message = "messages.label.taxid.{$application->country}";
			$caption = $this->translator->translate($message);
			if ($caption !== $message) {
				/** @var TextInput $input */
				$input = $form->getComponent('companyTaxId');
				$input->caption = "{$caption}:";
			}
		}
	}


	/**
	 * @param stdClass $values
	 * @param string $name
	 * @param Row[] $dates
	 * @param SessionSection $sessionSection
	 * @throws TrainingDateNotUpcomingException
	 */
	private function checkTrainingDate(stdClass $values, string $name, array $dates, SessionSection $sessionSection): void
	{
		if (!isset($dates[$values->trainingId])) {
			$this->formDataLogger->log($values, $name, $sessionSection);
			throw new TrainingDateNotUpcomingException($values->trainingId, $dates);
		}
	}

}
