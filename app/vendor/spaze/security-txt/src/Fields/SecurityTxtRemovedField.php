<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

/**
 * A field a draft had and RFC 9116 doesn't, by its name in lowercase
 */
enum SecurityTxtRemovedField: string
{

	case Signature = 'signature';

}
