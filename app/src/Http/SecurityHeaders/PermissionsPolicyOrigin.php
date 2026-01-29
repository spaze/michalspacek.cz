<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

enum PermissionsPolicyOrigin: string
{

	case None = '';
	case Self = 'self';
	case Src = 'src';

}
