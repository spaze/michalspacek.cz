<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

use Nette\Utils\Strings;

class Vrana implements ApplicationSourceResolverInterface
{

	public function isTrainingApplicationOwner(string $note): bool
	{
		return (str_contains(Strings::lower($note), 'jakub vrána') || str_contains(Strings::lower($note), 'od jakuba'));
	}

}
