<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Robots;

enum RobotsRule: string
{

	case NoFollow = 'nofollow';
	case NoIndex = 'noindex';

}
