<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\SecurityTxtValidator\ValidationResult\LogoExtraCssClass;
use MichalSpacekCz\SecurityTxtValidator\ValidationResult\LogoExtraIcon;

abstract class LayoutTemplateParameters
{

	public bool $showIntro = false;
	public ?LogoExtraCssClass $logoExtraCssClass = null;
	public ?LogoExtraIcon $logoExtraIcon = null;
	public bool $accountFeatureEnabled = false;

}
