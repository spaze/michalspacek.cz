<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use AllowDynamicProperties;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Arrays;
use stdClass;

#[AllowDynamicProperties]
final class DefaultTemplate extends Template
{

	/** @var list<stdClass> */
	public array $flashes = [];


	/**
	 * A copy of Nette\Bridges\ApplicationLatte\DefaultTemplate::setParameters() but it accepts an object
	 */
	public function setParameters(object $params): DefaultTemplate
	{
		return Arrays::toObject((array)$params, $this);
	}


	/**
	 * A copy of Nette\Bridges\ApplicationLatte\DefaultTemplate::setParameters()
	 *
	 * @param array<string, string|int> $params
	 */
	public function setParametersArray(array $params): DefaultTemplate
	{
		return Arrays::toObject($params, $this);
	}

}
