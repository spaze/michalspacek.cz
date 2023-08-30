<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use Closure;
use MichalSpacekCz\Test\PrivateProperty;
use Nette\Application\AbortException;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;
use ReflectionException;

class ApplicationPresenter
{

	/**
	 * @throws ReflectionException
	 */
	public function setLinkCallback(Application $application, Closure $buildLink): void
	{
		PrivateProperty::setValue($application, 'presenter', new class ($buildLink) extends Presenter {

			/**
			 * @param Closure(string, list<mixed>): string $buildLink
			 * @noinspection PhpMissingParentConstructorInspection
			 */
			public function __construct(
				private readonly Closure $buildLink,
			) {
			}


			/**
			 * @param list<mixed>|mixed $args
			 */
			public function link(string $destination, $args = []): string
			{
				$args = func_num_args() < 3 && is_array($args)
					? $args
					: array_slice(func_get_args(), 1);
				return ($this->buildLink)($destination, array_values($args));
			}

		});
	}


	public function expectSendResponse(callable $function): bool
	{
		try {
			$function();
			return false;
		} catch (AbortException) {
			return true;
		}
	}

}
