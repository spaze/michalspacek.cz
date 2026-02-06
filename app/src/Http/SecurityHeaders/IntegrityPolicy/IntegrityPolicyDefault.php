<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\IntegrityPolicy;

use MichalSpacekCz\Http\SecurityHeaders\ReportingApiEndpointName;
use MichalSpacekCz\Http\StructuredHeaders;
use Nette\Http\IResponse;
use Override;

final readonly class IntegrityPolicyDefault implements IntegrityPolicy
{

	public function __construct(
		private IResponse $httpResponse,
		private StructuredHeaders $structuredHeaders,
	) {
	}


	#[Override]
	public function set(): void
	{
		$this->httpResponse->setHeader(IntegrityPolicy::HEADER_NAME, $this->structuredHeaders->get([
			'blocked-destinations' => IntegrityPolicyBlockedDestination::Script,
			'endpoints' => ReportingApiEndpointName::Default,
		]));
	}

}
