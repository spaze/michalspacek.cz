<?php
declare(strict_types = 1);

namespace MichalSpacekCz\EasterEgg;

enum NetteCve202015227View: string
{

	case Ifconfig = 'nette.micro-ifconfig';
	case Ls = 'nette.micro-ls';
	case NotFound = 'nette.micro-not-found';
	case NotRecognized = 'nette.micro-not-recognized';
	case Wget = 'nette.micro-wget';

}
