<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter\TexyPhraseHandler;

use Composer\Pcre\Preg;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Articles\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Training\Company\CompanyTrainings;
use MichalSpacekCz\Training\TrainingLocales;
use MichalSpacekCz\Training\Trainings\Trainings;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Arrays;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

final readonly class TexyPhraseHandlerLinks
{

	public function __construct(
		private TrainingLocales $trainingLocales,
		private LocaleLinkGenerator $localeLinkGenerator,
		private BlogPostLocaleUrls $blogPostLocaleUrls,
	) {
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getLink(string $prefix, string $url, string $locale): string
	{
		$args = Preg::split('/[\s,]+/', $url);
		if ($args === []) {
			throw new ShouldNotHappenException('Preg::split() should always return a non-empty array');
		}
		$action = array_shift($args);
		if ($action === '') {
			throw new InvalidLinkException("No link specified in [{$prefix}]");
		}
		if (Arrays::contains([Trainings::TRAINING_ACTION, CompanyTrainings::COMPANY_TRAINING_ACTION], $action)) {
			if (!isset($args[0]) || $args[0] === '') {
				throw new InvalidLinkException("No training specified in [{$prefix}{$url}]");
			}
			$actions = $this->trainingLocales->getLocaleActions($args[0]);
			if ($actions === []) {
				throw new InvalidLinkException("Training linked in [{$prefix}{$url}] doesn't exist");
			}
			if (!isset($actions[$locale])) {
				throw new InvalidLinkException("Training linked in [{$prefix}{$url}] doesn't exist in locale {$locale}");
			}
			$args = [$actions[$locale]];
		}
		return $this->getLinkWithParams($action, [$locale => $args], $locale);
	}


	/**
	 * @param non-empty-array<string, list<string>|array<string, string|null>> $params
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	private function getLinkWithParams(string $destination, array $params, string $locale): string
	{
		$defaultParams = current($params);
		$this->localeLinkGenerator->setDefaultParams($params, $defaultParams);
		$links = $this->localeLinkGenerator->allLinks($destination, $params);
		if (!isset($links[$locale])) {
			throw new InvalidLinkException(sprintf("Unable to generate link to %s for locale %s with params %s", $destination, $locale, Json::encode($params)));
		}
		return $links[$locale];
	}


	/**
	 * @throws InvalidLinkException
	 * @throws JsonException
	 */
	public function getBlogLink(string $prefix, string $url, string $locale): string
	{
		$args = explode('#', $url);
		$fragment = !isset($args[1]) || trim($args[1]) === '' ? '' : "#{$args[1]}";

		if ($args[0] === '') {
			throw new InvalidLinkException("No link specified in [{$prefix}]");
		}

		$params = [];
		foreach ($this->blogPostLocaleUrls->get($args[0]) as $post) {
			$params[$post->getLocale()] = ['slug' => $post->getSlug(), 'preview' => $post->getPreviewKey()];
		}
		if ($params === []) {
			throw new InvalidLinkException("Blog post linked in [{$prefix}{$url}] doesn't exist");
		}
		if (!isset($params[$locale])) {
			throw new InvalidLinkException("Blog post linked in [{$prefix}{$url}] doesn't exist in locale {$locale}");
		}
		return $this->getLinkWithParams("Www:Post:default{$fragment}", $params, $locale);
	}

}
