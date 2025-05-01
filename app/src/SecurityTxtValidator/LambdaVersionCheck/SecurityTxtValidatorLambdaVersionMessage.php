<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use MichalSpacekCz\Application\UiControl;

final class SecurityTxtValidatorLambdaVersionMessage extends UiControl
{

	public function __construct(
		private readonly SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
	) {
	}


	public function render(): void
	{
		$installedVersion = $this->lambdaVersionCheck->getInstalledVersion();
		$lambdaVersion = $this->lambdaVersionCheck->getLastSeenVersion()?->getVersion();
		if ($lambdaVersion === null || $installedVersion->equals($lambdaVersion)) {
			return;
		}
		$this->template->setParameters(new SecurityTxtValidatorLambdaVersionMessageTemplateParameters(
			$installedVersion,
			$lambdaVersion,
			SecurityTxtValidatorLambdaVersionCheck::PACKAGE_NAME,
		));
		$this->template->render(__DIR__ . '/securityTxtValidatorLambdaVersionMessage.latte');
	}

}
