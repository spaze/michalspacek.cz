<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http;

use MichalSpacekCz\Http\SecurityHeaders;

interface SecurityHeadersFactory
{

	/**
	 * @param array<string|string[]> $permissionsPolicy
	 */
	public function create(array $permissionsPolicy): SecurityHeaders;

}
