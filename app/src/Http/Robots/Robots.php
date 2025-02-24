<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\Robots;

use Nette\Http\IResponse;

final class Robots
{

	public function __construct(
		private readonly IResponse $httpResponse,
	) {
	}


	/**
	 * @param list<RobotsRule> $rules
	 */
	public function setHeader(array $rules): void
	{
		$value = implode(', ', array_map(fn(RobotsRule $rule): string => $rule->value, $rules));
		$this->httpResponse->addHeader('X-Robots-Tag', $value);
	}

}
