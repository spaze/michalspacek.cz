<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

enum CheckExitStatus: int
{

	case Ok = 0;
	case Error = 1;
	case NoFile = 2;
	case FileError = 3;

}
