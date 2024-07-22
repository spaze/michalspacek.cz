<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use MichalSpacekCz\Utils\Hash;
use Nette\Utils\Html;
use Nette\Utils\Strings;
use Stringable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Texy\Texy;

class TexyFormatter
{

	private const string CACHE_KEY_DELIMITER = '|';

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
		private readonly bool $allowedLongWords,
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
		$this->texy->allowed['longwords'] = $this->allowedLongWords;
		$this->texy->addHandler('phrase', $this->phraseHandler->solve(...));
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
	public function format(string $text): Html
	{
		$texy = $this->texy ?? $this->getTexy();
		return $this->replace($text . self::CACHE_KEY_DELIMITER . __FUNCTION__, $texy, function () use ($texy, $text): string {
			return Strings::replace($texy->process($text), '~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1');
		});
	}


	/**
	 * Format string.
	 */
	public function formatBlock(string $text): Html
	{
		$texy = $this->texy ?? $this->getTexy();
		return $this->replace($text . self::CACHE_KEY_DELIMITER . __FUNCTION__, $texy, function () use ($texy, $text): string {
			return $texy->process($text);
		});
	}


	/**
	 * @param callable(): string $callback
	 */
	private function replace(string $key, Texy $texy, callable $callback): Html
	{
		if ($this->cacheResult) {
			$result = $this->cache->get($this->getCacheKey($key, $texy), function (ItemInterface $item, bool &$save) use ($callback): string {
				$item->expiresAt(null);
				$save = true;
				return $callback();
			});
		} else {
			$result = $callback();
		}

		$replacements = [];
		foreach ($this->placeholders as $placeholder) {
			$replacements[$placeholder::getId()] = $placeholder->replace(...);
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


	public function getCacheKey(string $text, Texy $texy): string
	{
		$key = "{$text}|" . serialize($texy->allowedTags);
		// Make the key shorter because Symfony Cache stores it in comments in cache files
		// Don't hash the locale to make it visible inside cache files
		return Hash::nonCryptographic($key) . '.' . $this->translator->getDefaultLocale();
	}


	public function disableCache(): self
	{
		$this->cacheResult = false;
		return $this;
	}


	public function withTexy(Texy $texy): self
	{
		$texyFormatter = clone $this;
		$texyFormatter->texy = $texy;
		return $texyFormatter;
	}

}
