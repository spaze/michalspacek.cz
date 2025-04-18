<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Media\Resources;

use Override;

final class TalkMediaResources extends MediaResources
{

	#[Override]
	protected function getSubDir(): string
	{
		return 'talks';
	}

}
