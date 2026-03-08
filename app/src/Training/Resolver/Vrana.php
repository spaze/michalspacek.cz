<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

use Nette\Utils\Strings;
use Override;

final class Vrana implements ApplicationSourceResolver
{

	#[Override]
	public function getTrainingApplicationOwner(string $note): ?string
	{
		if (str_contains(Strings::lower($note), 'jakub vrána') || str_contains(Strings::lower($note), 'od jakuba')) {
			return 'jakub-vrana';
		}
		return null;
	}

}
