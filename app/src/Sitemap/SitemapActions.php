<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Sitemap;

use Composer\Pcre\Regex;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\InvalidPresenterException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\DI\Container;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * The actions of the module the sitemap is for, with their InSitemap and NotInSitemap attributes.
 */
final readonly class SitemapActions
{

	private const string MODULE = 'Www';


	public function __construct(
		private Container $container,
		private IPresenterFactory $presenterFactory,
	) {
	}


	/**
	 * @return list<SitemapAction>
	 */
	public function getActions(): array
	{
		$actions = [];
		foreach ($this->container->findByType(IPresenter::class) as $name) {
			$class = $this->container->getServiceType($name);
			$presenter = $this->getPresenterName($class);
			if ($presenter === null) {
				continue;
			}
			$methods = $inSitemap = $notInSitemap = [];
			foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$result = Regex::matchStrictGroups('/^(?:action|render)([A-Z][A-Za-z0-9]*)\z/', $method->getName());
				if (!$result->matched || str_starts_with($method->class, 'Nette\\')) {
					continue;
				}
				$destination = $presenter . ':' . lcfirst($result->matches[1]);
				$methods[$destination][] = $method->getName();
				$inSitemap[$destination] ??= [];
				$notInSitemap[$destination] ??= [];
				array_push($inSitemap[$destination], ...$this->getAttributes($method, InSitemap::class));
				array_push($notInSitemap[$destination], ...$this->getAttributes($method, NotInSitemap::class));
			}
			foreach ($methods as $destination => $methodNames) {
				$actions[] = new SitemapAction($destination, $methodNames, $inSitemap[$destination], $notInSitemap[$destination]);
			}
			foreach ($this->getTemplateActions($class) as $action) {
				$destination = $presenter . ':' . $action;
				if (!isset($methods[$destination])) {
					$actions[] = new SitemapAction($destination, [], [], []);
				}
			}
		}
		return $actions;
	}


	/**
	 * The presenter name for a presenter class of the module, null for other classes. A class of the module named any other
	 * way throws, or its actions would go unchecked.
	 *
	 * @param class-string $class
	 */
	private function getPresenterName(string $class): ?string
	{
		$result = Regex::matchStrictGroups(sprintf('/^MichalSpacekCz\\\\Presentation\\\\%s\\\\([A-Za-z0-9]+)\\\\\1Presenter\z/', self::MODULE), $class);
		if (!$result->matched) {
			if (str_starts_with($class, sprintf('MichalSpacekCz\\Presentation\\%s\\', self::MODULE))) {
				throw new ShouldNotHappenException(sprintf("%s isn't named Foo\\FooPresenter like the other %s presenters, rename it or teach SitemapActions its presenter name", $class, self::MODULE));
			}
			return null;
		}
		$presenter = self::MODULE . ':' . $result->matches[1];
		try {
			$mapped = $this->presenterFactory->getPresenterClass($presenter);
		} catch (InvalidPresenterException $e) {
			throw new ShouldNotHappenException(sprintf("Presenter '%s' of %s doesn't map to a class, has the presenter mapping changed?", $presenter, $class), previous: $e);
		}
		if ($mapped !== $class) {
			throw new ShouldNotHappenException(sprintf("Presenter '%s' maps to %s, not %s, has the presenter mapping changed?", $presenter, $mapped, $class));
		}
		return $presenter;
	}


	/**
	 * Nette renders an action from its template alone, with no action or render method, so such a page has to be found
	 * by its template, a file next to the presenter named after the action, the layouts excluded.
	 *
	 * @param class-string $class
	 * @return list<string>
	 */
	private function getTemplateActions(string $class): array
	{
		$file = (new ReflectionClass($class))->getFileName();
		if ($file === false) {
			return [];
		}
		$templates = glob(dirname($file) . '/*.latte');
		if ($templates === false) {
			return [];
		}
		$actions = [];
		foreach ($templates as $template) {
			$action = basename($template, '.latte');
			if (!str_starts_with($action, '@')) {
				$actions[] = $action;
			}
		}
		return $actions;
	}


	/**
	 * @template T of object
	 * @param class-string<T> $attribute
	 * @return list<T>
	 */
	private function getAttributes(ReflectionMethod $method, string $attribute): array
	{
		return array_map(fn(ReflectionAttribute $attribute): object => $attribute->newInstance(), $method->getAttributes($attribute));
	}

}
