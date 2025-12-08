<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxt;

use MichalSpacekCz\Application\WebApplication;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\BadRequestException;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;
use Nette\IOException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class SecurityTxtResponseTest extends TestCase
{

	public function __construct(
		private readonly WebApplication $application,
		private readonly Response $httpResponse,
	) {
	}


	public function testGetResponse(): void
	{
		$response = new SecurityTxtResponse($this->application, $this->httpResponse, []);
		Assert::exception(function () use ($response) {
			$response->getResponse();
		}, BadRequestException::class, 'security.txt not configured for www.domain.example');

		$response = new SecurityTxtResponse($this->application, $this->httpResponse, [$this->application->getFqdn() => 'www.michalspacek.cz']);
		$contents = $response->getResponse()->getSource();
		assert(is_string($contents));
		Assert::contains('https://www.michalspacek.cz/kontakt', $contents);

		$response = new SecurityTxtResponse($this->application, $this->httpResponse, [$this->application->getFqdn() => 'non-existing-dir']);
		Assert::exception(function () use ($response) {
			$response->getResponse();
		}, IOException::class, "#Unable to read file '.*/files/non-existing-dir/security.txt'. Failed to open stream: No such file or directory#");
	}


	public function testAddRoute(): void
	{
		$routeList = new RouteList();
		new SecurityTxtResponse($this->application, $this->httpResponse, [])->addRoute($routeList);
		$routeLists = $routeList->getRouters();
		assert($routeLists[0] instanceof RouteList);
		Assert::same('WellKnown:', $routeLists[0]->getModule());
		$routers = $routeLists[0]->getRouters();
		assert($routers[0] instanceof Route);
		Assert::same(['presenter' => 'WellKnown', 'action' => 'securityTxt'], $routers[0]->getDefaults());
	}

}

TestCaseRunner::run(SecurityTxtResponseTest::class);
