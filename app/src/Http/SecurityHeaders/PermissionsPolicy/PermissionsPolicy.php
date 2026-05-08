<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy;

use MichalSpacekCz\Http\StructuredHeaders;
use MichalSpacekCz\Presentation\Www\BasePresenter;
use Nette\Application\Application;
use Nette\Http\IResponse;

final readonly class PermissionsPolicy
{

	public function __construct(
		private IResponse $httpResponse,
		private Application $application,
		private StructuredHeaders $structuredHeaders,
	) {
	}


	public function set(): void
	{
		$policy = [];
		foreach (PermissionsPolicyDirective::cases() as $directive) {
			$policy[$directive->value] = PermissionsPolicyOrigin::None;
		}
		$presenter = $this->application->getPresenter();
		if ($presenter instanceof BasePresenter) {
			$policy = array_merge($policy, $presenter->getPermissionsPolicy());
		}
		$this->httpResponse->setHeader('Permissions-Policy', $this->structuredHeaders->get($policy));
	}

}
