<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

use Nette\Http\IResponse;
use Override;

final readonly class IntegrityPolicyNone implements IntegrityPolicy
{

	public function __construct(
		private IResponse $httpResponse,
	) {
	}


	#[Override]
	public function set(): void
	{
		$this->httpResponse->deleteHeader(IntegrityPolicy::HEADER_NAME);
	}

}
