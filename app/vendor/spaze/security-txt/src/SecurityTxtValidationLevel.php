<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt;

enum SecurityTxtValidationLevel
{

	case NoInvalidValues;
	case AllowInvalidValues;
	case AllowInvalidValuesSilently;

}
