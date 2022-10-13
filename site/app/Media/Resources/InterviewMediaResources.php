<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Resources;

class InterviewMediaResources extends MediaResources
{

	protected function getSubDir(): string
	{
		return 'interviews';
	}

}
