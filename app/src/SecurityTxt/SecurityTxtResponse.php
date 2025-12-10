<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxt;

use MichalSpacekCz\Application\WebApplication;
use Nette\Application\BadRequestException;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Routers\RouteList;
use Nette\Http\IResponse;
use Nette\Utils\FileSystem;
use Spaze\SecurityTxt\SecurityTxtContentType;

final readonly class SecurityTxtResponse
{

	/**
	 * @param array<string, string> $dirs
	 */
	public function __construct(
		private WebApplication $application,
		private IResponse $httpResponse,
		private array $dirs,
	) {
	}


	public function getResponse(): TextResponse
	{
		$fqdn = $this->application->getFqdn();
		if (!isset($this->dirs[$fqdn])) {
			throw new BadRequestException('security.txt not configured for ' . $fqdn);
		}
		$contents = FileSystem::read(sprintf('%s/files/%s/security.txt', __DIR__, basename($this->dirs[$fqdn])));
		$this->httpResponse->setContentType(SecurityTxtContentType::CONTENT_TYPE, SecurityTxtContentType::CHARSET);
		return new TextResponse($contents);
	}


	public function addRoute(RouteList $router): void
	{
		$router->withModule('WellKnown')->addRoute('/.well-known/security.txt', ['presenter' => 'WellKnown', 'action' => 'securityTxt']);
	}

}
