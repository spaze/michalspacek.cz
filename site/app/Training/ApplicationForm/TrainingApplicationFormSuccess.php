<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationForm;

use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Templating\Exceptions\WrongTemplateClassException;
use MichalSpacekCz\Templating\TemplateFactory;
use MichalSpacekCz\Training\Applications\TrainingApplicationStorage;
use MichalSpacekCz\Training\Dates\TrainingDate;
use MichalSpacekCz\Training\Exceptions\CannotUpdateTrainingApplicationStatusException;
use MichalSpacekCz\Training\Exceptions\SpammyApplicationException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotAvailableException;
use MichalSpacekCz\Training\Exceptions\TrainingDateNotUpcomingException;
use MichalSpacekCz\Training\Exceptions\TrainingStatusIdNotIntException;
use MichalSpacekCz\Training\Mails\TrainingMails;
use Nette\Application\Application as NetteApplication;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\SessionSection;
use Nette\Utils\Html;
use ParagonIE\Halite\Alerts\HaliteAlert;
use PDOException;
use SodiumException;
use stdClass;
use Tracy\Debugger;

class TrainingApplicationFormSuccess
{

	public function __construct(
		private readonly TrainingApplicationFormSpam $formSpam,
		private readonly TrainingApplicationStorage $trainingApplicationStorage,
		private readonly TrainingMails $trainingMails,
		private readonly TemplateFactory $templateFactory,
		private readonly TrainingApplicationFormDataLogger $formDataLogger,
		private readonly NetteApplication $netteApplication,
	) {
	}


	/**
	 * @param callable(string): void $onSuccess
	 * @param callable(string): void $onError
	 * @param array<int, TrainingDate> $dates
	 * @param SessionSection<string> $sessionSection
	 * @throws HaliteAlert
	 * @throws SodiumException
	 * @throws TrainingStatusIdNotIntException
	 * @throws WrongTemplateClassException
	 */
	public function success(
		Form $form,
		callable $onSuccess,
		callable $onError,
		string $action,
		Html $name,
		array $dates,
		bool $multipleDates,
		SessionSection $sessionSection,
	): void {
		$values = $form->getValues();
		try {
			$this->formSpam->check($values);
			if ($multipleDates) {
				$this->checkTrainingDate($values, $action, $dates, $sessionSection);
				$date = $dates[$values->trainingId] ?? false;
			} else {
				$date = reset($dates);
			}
			if (!$date) {
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
				if (($sessionSection->get('application')[$action]['dateId'] ?? null) === $values->trainingId) {
					$applicationId = $this->trainingApplicationStorage->updateApplication(
						$date,
						$sessionSection->get('application')[$action]['id'],
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
					$sessionSection->set('application', array_merge($sessionSection->get('application'), [$action => null]));
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
			$sessionSection->set('trainingId', $date->getId());
			$sessionSection->set('name', $values->name);
			$sessionSection->set('email', $values->email);
			$sessionSection->set('company', $values->company);
			$sessionSection->set('street', $values->street);
			$sessionSection->set('city', $values->city);
			$sessionSection->set('zip', $values->zip);
			$sessionSection->set('country', $values->country);
			$sessionSection->set('companyId', $values->companyId);
			$sessionSection->set('companyTaxId', $values->companyTaxId);
			$sessionSection->set('note', $values->note);
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
	 * @param stdClass $values
	 * @param string $name
	 * @param array<int, TrainingDate> $dates
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
