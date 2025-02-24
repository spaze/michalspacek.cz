<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http;

use Nette\Http\IRequest;

final readonly class HttpInput
{

	public function __construct(
		private IRequest $request,
	) {
	}


	public function getPostString(string $key): ?string
	{
		$data = $this->request->getPost($key);
		if (!is_string($data)) {
			return null;
		}
		return $data;
	}


	/**
	 * @return array<mixed>|null
	 * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection Need to specify value type for the array, and it's 'mixed'
	 */
	public function getPostArray(string $key): ?array
	{
		$data = $this->request->getPost($key);
		if (!is_array($data)) {
			return null;
		}
		return $data;
	}

}
