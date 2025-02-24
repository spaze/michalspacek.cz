<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\FetchMetadata;

use Nette\Http\IRequest;

final readonly class FetchMetadata
{

	public function __construct(
		private IRequest $httpRequest,
	) {
	}


	public function getHeader(FetchMetadataHeader $header): ?string
	{
		return $this->httpRequest->getHeader($header->value);
	}


	/**
	 * @return array<value-of<FetchMetadataHeader>, string|null>
	 */
	public function getAllHeaders(): array
	{
		$headers = [];
		foreach (FetchMetadataHeader::cases() as $header) {
			$headers[$header->value] = $this->getHeader($header);
		}
		return $headers;
	}

}
