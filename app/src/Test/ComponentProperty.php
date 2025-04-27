<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test;

use Nette\ComponentModel\Component;
use Nette\ComponentModel\IContainer;

final class ComponentProperty
{

	public static function setParentAndName(Component $component, ?IContainer $parent, ?string $name): void
	{
		/**
		 * @noinspection PhpInternalEntityUsedInspection
		 * @phpstan-ignore method.internal
		 */
		$component->setParent($parent, $name);
	}

}
