<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords\Storage;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use stdClass;

final readonly class StorageAlgorithmAttributesFactory
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
		$data = $this->schemaProcessor->process($schema, $decoded);
		assert($data instanceof stdClass);
		assert(is_array($data->inner) && array_is_list($data->inner) || $data->inner === null);
		assert(is_array($data->outer) && array_is_list($data->outer) || $data->outer === null);
		assert(is_array($data->params) || $data->params === null);
		/** @var list<string>|null $inner */
		$inner = $data->inner;
		/** @var list<string>|null $outer */
		$outer = $data->outer;
		/** @var array<string, string|int>|null $params */
		$params = $data->params;
		return new StorageAlgorithmAttributes($inner, $outer, $params);
	}

}
