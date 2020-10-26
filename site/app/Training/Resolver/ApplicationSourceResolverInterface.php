<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\Resolver;

interface ApplicationSourceResolverInterface
{

	public function isTrainingApplicationOwner(string $note): bool;

}
