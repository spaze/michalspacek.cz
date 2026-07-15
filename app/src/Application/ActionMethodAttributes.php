<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Application\UI\Presenter;
use ReflectionAttribute;
use ReflectionException;
use ReflectionMethod;

final class ActionMethodAttributes
{

	/**
	 * Finds an attribute on the action or render method of the presenter's current action,
	 * so with the current action `foo`, on either actionFoo() or renderFoo().
	 *
	 * @template T of object
	 * @param class-string<T> $attribute
	 * @return ReflectionAttribute<T>|null
	 */
	public static function find(Presenter $presenter, string $attribute): ?ReflectionAttribute
	{
		$methodNames = [
			Presenter::formatActionMethod($presenter->getAction()),
			Presenter::formatRenderMethod($presenter->getAction()),
		];
		foreach ($methodNames as $methodName) {
			try {
				$method = new ReflectionMethod($presenter, $methodName);
			} catch (ReflectionException) {
				continue;
			}
			$attributes = $method->getAttributes($attribute);
			if ($attributes !== []) {
				return $attributes[0];
			}
		}
		return null;
	}

}
