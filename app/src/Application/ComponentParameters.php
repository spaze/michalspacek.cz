<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Application\Exceptions\ParameterNotStringException;
use Nette\Application\UI\Component;

class ComponentParameters
{

	/**
	 * @return array<string, string|null>
	 * @throws ParameterNotStringException
	 */
	public function getStringParameters(Component $component): array
	{
		$params = [];
		foreach ($component->getParameters() as $name => $value) {
			$name = (string)$name;
			if ($value === null || is_string($value)) {
				$params[$name] = $value;
			} else {
				throw new ParameterNotStringException($name, get_debug_type($value));
			}
		}
		return $params;
	}

}
