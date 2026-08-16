<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Fields;

enum SecurityTxtField: string
{

	case Acknowledgments = 'Acknowledgments';
	case BugBounty = 'Bug-Bounty';
	case Canonical = 'Canonical';
	case Contact = 'Contact';
	case Csaf = 'CSAF'; // Common Security Advisory Framework provider metadata location
	case Encryption = 'Encryption';
	case Expires = 'Expires';
	case Hiring = 'Hiring';
	case Policy = 'Policy';
	case PreferredLanguages = 'Preferred-Languages';

}
