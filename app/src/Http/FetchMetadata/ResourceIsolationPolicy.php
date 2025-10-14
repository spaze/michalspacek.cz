<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\FetchMetadata;

use Nette\Application\Application;
use Nette\Application\IPresenter;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Utils\Arrays;
use ReflectionException;
use ReflectionMethod;
use Tracy\Debugger;

final readonly class ResourceIsolationPolicy
{

	public function __construct(
		private FetchMetadata $fetchMetadata,
		private IRequest $httpRequest,
		private Application $application,
		private bool $reportOnly,
	) {
	}


	public function install(): void
	{
		$this->application->onPresenter[] = function (Application $application, IPresenter $presenter): void {
			if ($presenter instanceof Presenter) {
				$presenter->onStartup[] = function () use ($presenter): void {
					if (!$this->isRequestAllowed($presenter)) {
						$this->logRequest($presenter);
						if (!$this->reportOnly) {
							$presenter->forward(':Www:Forbidden:', ['message' => 'messages.forbidden.crossSite']);
						}
					}
				};
			}
		};
	}


	/**
	 * Inspired by https://web.dev/articles/fetch-metadata#implementing_a_resource_isolation_policy
	 */
	public function isRequestAllowed(Presenter $presenter): bool
	{
		if ($presenter->getRequest()?->getMethod() === AppRequest::FORWARD) {
			return true;
		}
		// Allow requests from browsers which don't send Fetch Metadata
		if ($this->fetchMetadata->getHeader(FetchMetadataHeader::Site) === null) {
			return true;
		}
		// Allow same-site and browser-initiated requests
		if (Arrays::contains(['same-origin', 'same-site', 'none'], $this->fetchMetadata->getHeader(FetchMetadataHeader::Site))) {
			return true;
		}
		// Allow simple top-level navigations except <object> and <embed>
		if (
			$this->fetchMetadata->getHeader(FetchMetadataHeader::Mode) === 'navigate'
			&& $this->httpRequest->isMethod(IRequest::Get)
			&& !Arrays::contains(['object', 'embed'], $this->fetchMetadata->getHeader(FetchMetadataHeader::Dest))
		) {
			return true;
		}

		// [OPTIONAL] Exempt paths/endpoints meant to be served cross-origin
		// In this app, presenter's action or render methods with the ResourceIsolationPolicyCrossSite attribute are allowed to be called cross-site
		if (
			$this->isCallableCrossSite($presenter, Presenter::formatActionMethod($presenter->action))
			|| $this->isCallableCrossSite($presenter, Presenter::formatRenderMethod($presenter->action))
		) {
			return true;
		}

		// Reject all other requests that are cross-site and not navigational
		return false;
	}


	private function isCallableCrossSite(Presenter $presenter, string $method): bool
	{
		try {
			$method = new ReflectionMethod($presenter, $method);
		} catch (ReflectionException) {
			return false;
		}
		$attributes = $method->getAttributes(ResourceIsolationPolicyCrossSite::class);
		return $attributes !== [];
	}


	private function logRequest(Presenter $presenter): void
	{
		$headers = [];
		foreach ($this->fetchMetadata->getAllHeaders() as $header => $value) {
			$headers[] = sprintf('%s: %s', $header, $value ?? '[not sent]');
		}
		$message = sprintf(
			'%s %s; action: %s; param names: %s; headers: %s',
			$this->httpRequest->getMethod(),
			$this->httpRequest->getUrl()->getAbsoluteUrl(),
			$presenter->getAction(true),
			implode(', ', array_keys($presenter->getParameters())),
			implode(', ', $headers),
		);
		Debugger::log($message, 'cross-site');
	}

}
