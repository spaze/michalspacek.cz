<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\SecurityTxtValidator;

use DateTimeImmutable;
use MichalSpacekCz\DateTime\DateIntervalFormatter;
use MichalSpacekCz\Presentation\Admin\BasePresenter;
use MichalSpacekCz\SecurityTxtValidator\Forms\CheckLambdaVersionFormFactory;
use MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck\SecurityTxtValidatorLambdaVersionCheck;
use Nette\Forms\Form;
use Nette\Utils\Html;

final class SecurityTxtValidatorPresenter extends BasePresenter
{

	public function __construct(
		private readonly CheckLambdaVersionFormFactory $formFactory,
		private readonly SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
		private readonly DateIntervalFormatter $dateIntervalFormatter,
	) {
		parent::__construct();
	}


	public function actionLambdaVersion(): void
	{
		$pageHeader = Html::el()
			->setText('Versions of ')
			->addHtml(Html::el('em')->setText(SecurityTxtValidatorLambdaVersionCheck::PACKAGE_NAME));
		$this->template->pageTitle = $pageHeader->getText();
		$this->template->pageHeader = $pageHeader;
		$installedVersion = $this->lambdaVersionCheck->getInstalledVersion();
		$lastSeenVersion = $this->lambdaVersionCheck->getLastSeenVersion();
		$this->template->installedVersion = $installedVersion;
		$this->template->lambdaVersion = $lastSeenVersion?->getVersion();
		$this->template->versionMatch = $lastSeenVersion !== null && $installedVersion->equals($lastSeenVersion->getVersion());
		$this->template->lastCheck = $lastSeenVersion?->getLastCheck();
		$this->template->lastCheckAgo = $lastSeenVersion !== null ? $this->dateIntervalFormatter->toMinutesSecondsAgo($lastSeenVersion->getLastCheck()->diff(new DateTimeImmutable())) : null;
	}


	protected function createComponentCheckLambdaVersion(): Form
	{
		return $this->formFactory->create(function (bool $matches): void {
			if ($matches) {
				$this->flashMessage(Html::el()
					->setText('The Lambda version of ')
					->addHtml(Html::el('em')->setText(SecurityTxtValidatorLambdaVersionCheck::PACKAGE_NAME))
					->addText(' matches the app version'));
			}
			$this->redirect('Homepage:default');
		});
	}

}
