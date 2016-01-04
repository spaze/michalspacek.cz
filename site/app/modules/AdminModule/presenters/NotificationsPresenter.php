<?php
namespace App\AdminModule\Presenters;

use MichalSpacekCz\Training;

/**
 * Notifications presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class NotificationsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Training\Notifications */
	protected $trainingNotifications;

	/** @var \MichalSpacekCz\Training\Statuses */
	protected $trainingStatuses;

	/** @var \MichalSpacekCz\Notifier\Vrana */
	protected $vranaNotifier;

	/** @var array */
	private $applications;


	/**
	 * @param \MichalSpacekCz\Training\Notifications $trainingNotifications,
	 * @param \MichalSpacekCz\Training\Statuses $trainingStatuses
	 * @param \MichalSpacekCz\Notifier\Vrana $vranaNotifier
	 */
	public function __construct(
		Training\Notifications $trainingNotifications,
		Training\Statuses $trainingStatuses,
		\MichalSpacekCz\Notifier\Vrana $vranaNotifier
	)
	{
		$this->trainingNotifications = $trainingNotifications;
		$this->trainingStatuses = $trainingStatuses;
		$this->vranaNotifier = $vranaNotifier;
	}

	public function actionDefault()
	{
		$this->template->pageTitle = 'Notifikace k odeslání';
		$this->applications = array();
		foreach ($this->trainingNotifications->getApplications() as $application) {
			$this->applications[$application->id] = $application;
		}
		$this->template->applications = $this->applications;
	}

	protected function createComponentNotifications($formName)
	{
		$form = new \MichalSpacekCz\Form\TrainingNotifications($this, $formName, $this->applications);
		$form->onSuccess[] = $this->submittedNotifications;
	}

	public function submittedNotifications(\MichalSpacekCz\Form\TrainingNotifications $form)
	{
		$values = $form->getValues();
		$sent = 0;
		foreach ($values->applications as $id => $checked) {
			if (empty($checked) || !isset($this->applications[$id])) {
				continue;
			}
			$this->vranaNotifier->addTrainingApplication($this->applications[$id]);
			$this->trainingStatuses->updateStatus($id, Training\Statuses::STATUS_NOTIFIED);
			$sent++;
		}
		if ($sent) {
			$this->flashMessage('Počet odeslaných notifikací: ' . $sent);
		} else {
			$this->flashMessage('Nebyla odeslána žádná notifikace', 'notice');
		}
		$this->redirect('Homepage:');
	}

}
