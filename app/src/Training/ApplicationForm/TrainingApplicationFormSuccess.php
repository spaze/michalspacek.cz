<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationSessionSection;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Exceptions\CannotUpdateTrainingApplicationStatusException;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotAvailableException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotUpcomingException;
use MichalSpacekCz\Training\Mails\TrainingMails;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Presenter;
use Nette\Utils\Html;
use ParagonIE\Halite\Alerts\HaliteAlert;
use PDOException;
use SodiumException;
use Tracy\Debugger;

final readonly class TrainingApplicationFormSuccess
{

	public function __construct(
		private TrainingApplicationFormSpam $formSpam,
		private TrainingApplicationStorage $trainingApplicationStorage,
		private TrainingMails $trainingMails,
		private TemplateFactory $templateFactory,
		private TrainingApplicationFormDataLogger $formDataLogger,
		private NetteApplication $netteApplication,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param array<int, TrainingDate> $dates
	 * @throws HaliteAlert
	 * @throws SodiumException
	 * @throws WrongTemplateClassException
	 */
	public function success(
		UiForm $form,
		callable $onSuccess,
		callable $onError,
		string $action,
		Html $name,
		array $dates,
		bool $multipleDates,
		TrainingApplicationSessionSection $sessionSection,
	): void {
		$values = $form->getFormValues();
		assert(is_string($values->name));
		assert(is_string($values->email));
		assert(is_string($values->company));
		assert(is_string($values->street));
		assert(is_string($values->city));
		assert(is_string($values->zip));
		assert(is_string($values->country));
		assert(is_string($values->companyId));
		assert(is_string($values->companyTaxId));
		assert(is_string($values->note));
		try {
			$this->formSpam->check($values->name, $values->company, $values->companyId, $values->companyTaxId, $values->note);
			if ($multipleDates) {
				assert(is_int($values->trainingId));
				$this->checkTrainingDate((array)$values, $action, $values->trainingId, $dates, $sessionSection);
				$date = $dates[$values->trainingId];
			} else {
				$date = reset($dates);
			}
			if ($date === false) {
				throw new TrainingDateNotAvailableException();
			}

			if ($date->isTentative()) {
				$this->trainingApplicationStorage->addInvitation(
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
				$applicationId = $sessionSection->getApplicationIdByDateId($action, $date->getId());
				if ($applicationId !== null) {
					$this->trainingApplicationStorage->updateApplication(
						$date,
						$applicationId,
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
					$sessionSection->removeApplication($action);
				} else {
					$applicationId = $this->trainingApplicationStorage->addApplication(
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
				$presenter = $this->netteApplication->getPresenter();
				if (!$presenter instanceof Presenter) {
					throw new ShouldNotHappenException(sprintf("The presenter should be a '%s' but it's a %s", Presenter::class, get_debug_type($presenter)));
				}
				$this->trainingMails->sendSignUpMail(
					$applicationId,
					$this->templateFactory->createTemplate($presenter),
					$values->email,
					$values->name,
					$date->getStart(),
					$date->getEnd(),
					$action,
					$name,
					$date->isRemote(),
					$date->getVenueName(),
					$date->getVenueNameExtended(),
					$date->getVenueAddress(),
					$date->getVenueCity(),
				);
			}
			$sessionSection->setOnSuccess($date, $values);
			$onSuccess($action);
		} catch (SpammyApplicationException) {
			$onError('messages.trainings.spammyapplication');
		} catch (TrainingDateNotUpcomingException) {
			$onError('messages.trainings.wrongdateapplication');
		} catch (TrainingDateNotAvailableException $e) {
			Debugger::log($e);
			$onError('messages.trainings.wrongdateapplication');
		} catch (PDOException | CannotUpdateTrainingApplicationStatusException $e) {
			Debugger::log($e, Debugger::ERROR);
			$onError($e->getTraceAsString() . 'messages.trainings.errorapplication');
		}
	}


	/**
	 * @param array<array-key, mixed> $values
	 * @param array<int, TrainingDate> $dates
	 * @throws TrainingDateNotUpcomingException
	 */
	private function checkTrainingDate(array $values, string $name, int $dateId, array $dates, TrainingApplicationSessionSection $sessionSection): void
	{
		if (!isset($dates[$dateId])) {
			$this->formDataLogger->log($values, $name, $dateId, $sessionSection);
			throw new TrainingDateNotUpcomingException($dateId, $dates);
		}
	}

}
