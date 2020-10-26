<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

use Nette\Utils\Strings;

class Vrana implements ApplicationSourceResolverInterface
{

	public function isTrainingApplicationOwner(string $note): bool
	{
		return (Strings::contains(Strings::lower($note), 'jakub vrána') || Strings::contains(Strings::lower($note), 'od jakuba'));
	}

}
