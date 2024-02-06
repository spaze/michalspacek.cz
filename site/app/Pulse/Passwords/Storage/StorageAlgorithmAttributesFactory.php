<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

readonly class StorageAlgorithmAttributesFactory
{

	public function __construct(
		private Processor $schemaProcessor,
	) {
	}


	/**
	 * @throws JsonException
	 * @throws ValidationException
	 */
	public function get(?string $json): StorageAlgorithmAttributes
	{
		if ($json === null || $json === '') {
			return new StorageAlgorithmAttributes(null, null, null);
		}
		$decoded = Json::decode($json, true);
		$schema = Expect::structure([
			'inner' => Expect::listOf(Expect::string()),
			'outer' => Expect::listOf(Expect::string()),
			'params' => Expect::arrayOf(Expect::anyOf(Expect::string(), Expect::int()), Expect::string()),
		]);
		/** @var object{inner:list<string>|null, outer:list<string>|null, params:array<string, string>|null} $data */
		$data = $this->schemaProcessor->process($schema, $decoded);
		return new StorageAlgorithmAttributes($data->inner, $data->outer, $data->params);
	}

}
