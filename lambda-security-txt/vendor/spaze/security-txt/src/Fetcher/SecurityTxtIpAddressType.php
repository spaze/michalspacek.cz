<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fetcher;

enum SecurityTxtIpAddressType: int
{

	case V4 = 1;
	case V6 = 2;

}
