<?php
namespace MichalSpacekCz\Training\Resolver;

use Nette\Utils\Strings;

class Vrana implements ApplicationSourceResolverInterface
{

	public function isTrainingApplicationOwner($note)
	{
		return (Strings::contains(Strings::lower($note), 'jakub vrána') || Strings::contains(Strings::lower($note), 'od jakuba'));
	}

}
