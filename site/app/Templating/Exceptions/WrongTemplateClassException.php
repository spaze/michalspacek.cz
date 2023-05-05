<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating\Exceptions;

use Throwable;

class WrongTemplateClassException extends \Exception
{

	/**
	 * @param class-string $actual
	 * @param class-string $expected
	 * @param Throwable|null $previous
	 */
	public function __construct(string $actual, string $expected, ?Throwable $previous = null)
	{
		parent::__construct(sprintf('The template should be an instance of %s but is an instance of %s', $expected, $actual), previous: $previous);
	}

}
