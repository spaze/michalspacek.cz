<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Symfony\Contracts\Cache\CacheInterface;
use Texy\Texy;

class TexyFormatter
{

	private ?Texy $texy = null;

	private bool $cacheResult = true;

	/**
	 * Static files root FQDN, no trailing slash.
	 */
	private string $staticRoot;

	/**
	 * Images root, just directory no FQDN, no leading slash, no trailing slash.
	 */
	private string $imagesRoot;

	/**
	 * Physical location root directory, no trailing slash.
	 */
	private string $locationRoot;

	/**
	 * Top heading level, used to avoid starting with H1.
	 */
	private int $topHeading = 1;


	/**
	 * @param list<TexyFormatterPlaceholder> $placeholders
	 */
	public function __construct(
		private readonly CacheInterface $cache,
		private readonly Translator $translator,
		private readonly TexyPhraseHandler $phraseHandler,
		private readonly array $placeholders,
		string $staticRoot,
		string $imagesRoot,
		string $locationRoot,
	) {
		$this->staticRoot = rtrim($staticRoot, '/');
		$this->imagesRoot = trim($imagesRoot, '/');
		$this->locationRoot = rtrim($locationRoot, '/');
	}


	/**
	 * Get static content URL root.
	 */
	public function getStaticRoot(): string
	{
		return $this->staticRoot;
	}


	/**
	 * Get absolute URL of the image.
	 */
	public function getImagesRoot(string $filename): string
	{
		return sprintf('%s/%s/%s', $this->staticRoot, $this->imagesRoot, ltrim($filename, '/'));
	}


	/**
	 * Set top heading level.
	 *
	 * @param int $level
	 * @return self
	 */
	public function setTopHeading(int $level): self
	{
		$this->topHeading = $level;
		if ($this->texy) {
			$this->texy->headingModule->top = $this->topHeading;
		}
		return $this;
	}


	/**
	 * Create Texy object.
	 *
	 * @return Texy
	 */
	public function getTexy(): Texy
	{
		$this->texy = new Texy();
		$this->texy->allowedTags = $this->texy::NONE;
		$this->texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$this->texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$this->texy->figureModule->widthDelta = false; // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		$this->texy->headingModule->generateID = true;
		$this->texy->headingModule->idPrefix = '';
		$this->texy->typographyModule->locale = substr($this->translator->getDefaultLocale(), 0, 2); // en_US → en
		$this->texy->allowed['phrase/del'] = true;
		$this->texy->addHandler('phrase', [$this->phraseHandler, 'solve']);
		$this->setTopHeading($this->topHeading);
		return $this->texy;
	}


	/**
	 * @param string $format
	 * @param list<string|int> $args
	 * @return Html<Html|string>
	 */
	public function substitute(string $format, array $args): Html
	{
		return $this->format(vsprintf($format, $args));
	}


	/**
	 * @param string $message
	 * @param list<string> $replacements
	 * @return Html<Html|string>
	 * @throws InvalidArgument
	 */
	public function translate(string $message, array $replacements = []): Html
	{
		return $this->substitute($this->translator->translate($message), $replacements);
	}


	/**
	 * Format string and strip surrounding P element.
	 *
	 * Suitable for "inline" strings like headers.
	 *
	 * @param string $text
	 * @param Texy|null $texy
	 * @return Html<Html|string>
	 */
	public function format(string $text, ?Texy $texy = null): Html
	{
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return Strings::replace(($texy ?? $this->getTexy())->process($text), '~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1');
		}));
	}


	/**
	 * Format string.
	 *
	 * @param string $text
	 * @param Texy|null $texy
	 * @return Html<Html|string>
	 */
	public function formatBlock(string $text, ?Texy $texy = null): Html
	{
		return $this->replace($this->cache("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return ($texy ?? $this->getTexy())->process($text);
		}));
	}


	/**
	 * @param Html<Html|string> $result
	 * @return Html<Html|string>
	 */
	private function replace(Html $result): Html
	{
		$replacements = [];
		foreach ($this->placeholders as $placeholder) {
			$replacements[$placeholder::getPlaceholder()] = $placeholder->replace(...);
		}

		$result = Strings::replace(
			(string)$result,
			'~\*\*([^:]+):([^*]+)\*\*~',
			function ($matches) use ($replacements): string {
				return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : '');
			},
		);
		return Html::el()->setHtml($result);
	}


	/**
	 * Cache formatted string.
	 *
	 * @param string $text
	 * @param callable(): string $callback
	 * @return Html
	 */
	private function cache(string $text, callable $callback): Html
	{
		if ($this->cacheResult) {
			$formatted = $this->cache->get($this->getCacheKey($text), $callback);
		} else {
			$formatted = $callback();
		}
		return Html::el()->setHtml($formatted);
	}


	private function getCacheKey(string $text): string
	{
		// Make the key shorter because Symfony Cache stores it in comments in cache files
		// Use MD5 to favor speed over security, which is not an issue here, and Symfony Cache itself uses MD5 as well
		// Don't hash the locale to make it visible inside cache files
		return md5($text) . '.' . $this->translator->getDefaultLocale();
	}


	public function disableCache(): self
	{
		$this->cacheResult = false;
		return $this;
	}

}
