<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

enum SecurityTxtField: string
{

	case Acknowledgments = 'Acknowledgments';
	case Canonical = 'Canonical';
	case Contact = 'Contact';
	case Encryption = 'Encryption';
	case Expires = 'Expires';
	case Hiring = 'Hiring';
	case Policy = 'Policy';
	case PreferredLanguages = 'Preferred-Languages';

}
