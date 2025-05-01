<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

enum LogoExtraCssClass: string
{

	case Error = 'error';
	case Valid = 'valid';
	case Warning = 'warning';
	case Invalid = 'invalid';

}
