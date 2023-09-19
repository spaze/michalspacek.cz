<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Stringable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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
	 * @param list<string|Stringable|int> $args
	 */
	public function substitute(string|Stringable $format, array $args): Html
	{
		array_walk($args, function (string|Stringable|int $value): string {
			return (string)$value;
		});
		return $this->format(vsprintf((string)$format, $args));
	}


	/**
	 * @param list<string> $replacements
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
	 */
	public function format(string $text, ?Texy $texy = null): Html
	{
		return $this->replace("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return Strings::replace(($texy ?? $this->getTexy())->process($text), '~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1');
		});
	}


	/**
	 * Format string.
	 */
	public function formatBlock(string $text, ?Texy $texy = null): Html
	{
		return $this->replace("{$text}|" . __FUNCTION__, function () use ($text, $texy): string {
			return ($texy ?? $this->getTexy())->process($text);
		});
	}


	/**
	 * @param callable(): string $callback
	 */
	private function replace(string $key, callable $callback): Html
	{
		if ($this->cacheResult) {
			$result = $this->cache->get($this->getCacheKey($key), function (ItemInterface $item, bool &$save) use ($callback): string {
				$item->expiresAt(null);
				$save = true;
				return $callback();
			});
		} else {
			$result = $callback();
		}

		$replacements = [];
		foreach ($this->placeholders as $placeholder) {
			$replacements[$placeholder::getPlaceholder()] = $placeholder->replace(...);
		}

		$result = Strings::replace(
			$result,
			'~\*\*([^:]+):([^*]+)\*\*~',
			function (array $matches) use ($replacements): string {
				return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : '');
			},
		);
		return Html::el()->setHtml($result);
	}


	public function getCacheKey(string $text): string
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
