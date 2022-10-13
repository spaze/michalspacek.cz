<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Resources;

class TalkMediaResources extends MediaResources
{

	protected function getSubDir(): string
	{
		return 'talks';
	}

}
