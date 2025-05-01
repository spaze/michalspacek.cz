<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

enum LogoExtraIcon: string
{

	case ExclamationTriangle = 'exclamation-triangle';
	case CertificateCheck = 'certificate-check';
	case CertificateOff = 'certificate-off';

}
