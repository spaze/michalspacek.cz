<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Http\SecurityHeaders\IntegrityPolicy;

use MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy\IntegrityPolicy;
use MichalSpacekCz\Test\Http\Response;
use Override;

final class IntegrityPolicyMock implements IntegrityPolicy
{

	private string $value = '';


	public function __construct(
		private readonly Response $httpResponse,
	) {
	}


	public function setValue(string $value): void
	{
		$this->value = $value;
	}


	#[Override]
	public function set(): void
	{
		$this->httpResponse->setHeader(IntegrityPolicy::HEADER_NAME, $this->value);
	}

}
