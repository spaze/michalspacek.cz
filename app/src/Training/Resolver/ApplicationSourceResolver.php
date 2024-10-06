<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

interface ApplicationSourceResolver
{

	public function isTrainingApplicationOwner(string $note): bool;

}
