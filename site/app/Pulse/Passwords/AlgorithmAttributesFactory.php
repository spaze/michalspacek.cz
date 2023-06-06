<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use Nette\Schema\Expect;
use Nette\Schema\Processor;
use Nette\Schema\ValidationException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class AlgorithmAttributesFactory
{

	public function __construct(
		private readonly Processor $schemaProcessor,
	) {
	}


	/**
	 * @throws JsonException
	 * @throws ValidationException
	 */
	public function get(?string $json): AlgorithmAttributes
	{
		if (!$json) {
			return new AlgorithmAttributes(null, null, null);
		}
		$decoded = Json::decode($json, true);
		$schema = Expect::structure([
			'inner' => Expect::listOf(Expect::string()),
			'outer' => Expect::listOf(Expect::string()),
			'params' => Expect::arrayOf(Expect::anyOf(Expect::string(), Expect::int()), Expect::string()),
		]);
		$data = $this->schemaProcessor->process($schema, $decoded);
		return new AlgorithmAttributes($data->inner, $data->outer, $data->params);
	}

}
