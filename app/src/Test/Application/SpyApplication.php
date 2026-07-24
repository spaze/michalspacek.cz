<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Application;

use Nette\Application\Application;
use Override;

/**
 * Stands in for the real application so `WebApplication::run()` can be driven in a test
 * without dispatching a presenter; `$ran` records whether the run was reached.
 */
final class SpyApplication extends Application
{

	private(set) bool $ran = false;


	/**
	 * @noinspection PhpMissingParentConstructorInspection
	 * @phpstan-ignore constructor.missingParentCall
	 */
	public function __construct()
	{
	}


	#[Override]
	public function run(): void
	{
		$this->ran = true;
	}

}
