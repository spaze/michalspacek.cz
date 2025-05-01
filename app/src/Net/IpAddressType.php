<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Net;

enum IpAddressType: int
{

	case V4 = 1;
	case V6 = 2;

}
