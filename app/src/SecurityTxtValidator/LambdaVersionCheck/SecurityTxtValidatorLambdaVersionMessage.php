<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use MichalSpacekCz\Application\UiControl;
use MichalSpacekCz\SecurityTxtValidator\SecurityTxtLibraryVersion;

final class SecurityTxtValidatorLambdaVersionMessage extends UiControl
{

	public function __construct(
		private readonly SecurityTxtValidatorLambdaVersionCheck $lambdaVersionCheck,
		private readonly SecurityTxtLibraryVersion $libraryVersion,
	) {
	}


	public function render(): void
	{
		$installedVersion = $this->libraryVersion->getInstalled();
		$lambdaVersion = $this->lambdaVersionCheck->getLastSeenVersion()?->getVersion();
		if ($lambdaVersion === null || $installedVersion->equals($lambdaVersion)) {
			return;
		}
		$this->template->setParameters(new SecurityTxtValidatorLambdaVersionMessageTemplateParameters(
			$installedVersion,
			$lambdaVersion,
			SecurityTxtLibraryVersion::PACKAGE_NAME,
		));
		$this->template->render(__DIR__ . '/securityTxtValidatorLambdaVersionMessage.latte');
	}

}
