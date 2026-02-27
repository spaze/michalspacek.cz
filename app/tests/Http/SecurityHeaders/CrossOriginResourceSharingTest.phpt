<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders;

use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\UI\InvalidLinkException;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class CrossOriginResourceSharingTest extends TestCase
{

	public function __construct(
		private readonly Request $httpRequest,
		private readonly Response $httpResponse,
		private readonly CrossOriginResourceSharing $crossOriginResourceSharing,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->localeLinkGenerator->setAllLinks([
			'cs_CZ' => 'https://www.michalspacek.cz.test/',
			'en_US' => 'https://www.michalspacek.com.test/',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpRequest->reset();
		$this->httpResponse->reset();
		$this->localeLinkGenerator->reset();
	}


	/**
	 * @return list<array{origin:string|null, allow:string|null}>
	 */
	public function getOrigin(): array
	{
		return [
			['origin' => null, 'allow' => null],
			['origin' => 'file:///foo/bar', 'allow' => null],
			['origin' => 'https://example.com', 'allow' => null],
			['origin' => 'https://michalspacek.cz.test', 'allow' => null],
			['origin' => 'https://www.michalspacek.cz.test', 'allow' => 'https://www.michalspacek.cz.test'],
			['origin' => 'https://michalspacek.com.test', 'allow' => null],
			['origin' => 'https://www.michalspacek.com.test', 'allow' => 'https://www.michalspacek.com.test'],
		];
	}


	/**
	 * @dataProvider getOrigin
	 */
	public function testAccessControlAllowOrigin(?string $origin, ?string $allow): void
	{
		if ($origin !== null) {
			$this->httpRequest->setHeader('Origin', $origin);
		}
		$this->crossOriginResourceSharing->accessControlAllowOrigin('Www:Homepage:');
		if ($allow !== null) {
			Assert::same($allow, $this->httpResponse->getHeader(CrossOriginResourceSharing::HEADER_NAME_ALLOW_ORIGIN));
		} else {
			Assert::null($this->httpResponse->getHeader(CrossOriginResourceSharing::HEADER_NAME_ALLOW_ORIGIN));
		}
	}


	public function testAccessControlAllowOriginInvalidSource(): void
	{
		$this->httpRequest->setHeader('Origin', 'https://www.michalspacek.cz.test');
		$this->localeLinkGenerator->willThrow(new InvalidLinkException());
		$this->crossOriginResourceSharing->accessControlAllowOrigin('source would throw');
		Assert::null($this->httpResponse->getHeader(CrossOriginResourceSharing::HEADER_NAME_ALLOW_ORIGIN));
	}

}

TestCaseRunner::run(CrossOriginResourceSharingTest::class);
