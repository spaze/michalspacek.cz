<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Feed;

interface ExportsOmittable
{

	public function omitExports(): bool;

}
