<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\LambdaVersionCheck;

use MichalSpacekCz\Application\DependencyVersion;

final class SecurityTxtValidatorLambdaVersionMessageTemplateParameters
{

	public function __construct(
		public DependencyVersion $installedVersion,
		public DependencyVersion $lambdaVersion,
		public string $package,
	) {
	}

}
