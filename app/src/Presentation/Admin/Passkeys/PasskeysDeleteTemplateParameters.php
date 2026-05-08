<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Admin\Passkeys;

final class PasskeysDeleteTemplateParameters
{

	public function __construct(
		public string $pageTitle,
		public string $name,
	) {
	}

}
