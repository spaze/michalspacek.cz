<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\FetchMetadata;

use MichalSpacekCz\Test\Http\Request;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FetchMetadataTest extends TestCase
{

	public function __construct(
		private readonly Request $httpRequest,
		private readonly FetchMetadata $fetchMetadata,
	) {
	}


	public function testGetHeader(): void
	{
		$this->httpRequest->setHeader(FetchMetadataHeader::Dest->value, 'document');
		Assert::same('document', $this->fetchMetadata->getHeader(FetchMetadataHeader::Dest));
		Assert::null($this->fetchMetadata->getHeader(FetchMetadataHeader::Site));
	}


	public function testGetAllHeaders(): void
	{
		$this->httpRequest->setHeader(FetchMetadataHeader::Dest->value, 'document');
		$expectedHeaders = [
			'Sec-Fetch-Dest' => 'document',
			'Sec-Fetch-Mode' => null,
			'Sec-Fetch-Site' => null,
			'Sec-Fetch-User' => null,
		];
		Assert::same($expectedHeaders, $this->fetchMetadata->getAllHeaders());
		Assert::null($this->fetchMetadata->getHeader(FetchMetadataHeader::Site));

		$this->httpRequest->setHeader(FetchMetadataHeader::Dest->value, 'document');
		$this->httpRequest->setHeader(FetchMetadataHeader::Mode->value, 'navigate');
		$this->httpRequest->setHeader(FetchMetadataHeader::Site->value, 'cross-site');
		$this->httpRequest->setHeader(FetchMetadataHeader::User->value, '?1');
		$expectedHeaders = [
			'Sec-Fetch-Dest' => 'document',
			'Sec-Fetch-Mode' => 'navigate',
			'Sec-Fetch-Site' => 'cross-site',
			'Sec-Fetch-User' => '?1',
		];
		Assert::same($expectedHeaders, $this->fetchMetadata->getAllHeaders());
	}

}

TestCaseRunner::run(FetchMetadataTest::class);
