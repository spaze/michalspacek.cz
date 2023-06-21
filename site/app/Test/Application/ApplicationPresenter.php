<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use Closure;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use ReflectionException;
use ReflectionProperty;

class ApplicationPresenter
{

	/**
	 * @throws ReflectionException
	 */
	public function setLinkCallback(Application $application, Closure $buildLink): void
	{
		$property = new ReflectionProperty($application, 'presenter');
		$property->setValue($application, new class ($buildLink) extends Presenter {

			/**
			 * @param Closure(string, string[]): string $buildLink
			 * @noinspection PhpMissingParentConstructorInspection
			 */
			public function __construct(
				private readonly Closure $buildLink,
			) {
			}


			public function link(string $destination, $args = []): string
			{
				$args = func_num_args() < 3 && is_array($args)
					? $args
					: array_slice(func_get_args(), 1);
				return ($this->buildLink)($destination, $args);
			}

		});
	}

}
