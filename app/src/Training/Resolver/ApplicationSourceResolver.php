<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

interface ApplicationSourceResolver
{

	public function getTrainingApplicationOwner(string $note): ?string;

}
