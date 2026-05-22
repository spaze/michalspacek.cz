<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Composer\Pcre\Regex;
use Contributte\Translation\Exceptions\InvalidArgument;
use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\Placeholders\TexyFormatterPlaceholder;
use MichalSpacekCz\Formatter\TexyPhraseHandler\TexyPhraseHandler;
use MichalSpacekCz\Utils\Hash;
use Nette\Utils\Html;
use Stringable;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Texy\Texy;

class TexyFormatter
{

	private const string CACHE_KEY_DELIMITER = '|';

	public const array ALLOWED_URL_SCHEMES = ['http', 'https'];

	private ?Texy $texy = null;

	private ?Texy $texyNoLongWords = null;

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
		if ($this->texy !== null) {
			$this->texy->headingModule->top = $this->topHeading;
		}
		if ($this->texyNoLongWords !== null) {
			$this->texyNoLongWords->headingModule->top = $this->topHeading;
		}
		return $this;
	}


	public function createTexy(): Texy
	{
		$texy = new Texy();
		$texy->allowedTags = Texy::NONE;
		$texy->imageModule->root = "{$this->staticRoot}/{$this->imagesRoot}";
		$texy->imageModule->fileRoot = "{$this->locationRoot}/{$this->imagesRoot}";
		$texy->figureModule->widthDelta = false; // prevents adding 'unsafe-inline' style="width: Xpx" attribute to <div class="figure">
		$texy->headingModule->generateID = true;
		$texy->headingModule->idPrefix = '';
		$texy->headingModule->top = $this->topHeading;
		$texy->typographyModule->locale = substr($this->translator->getDefaultLocale(), 0, 2); // en_US → en
		$texy->allowed['phrase/del'] = true;
		$texy->allowed['longwords'] = true;
		// Not Configurator::safeMode() as that also forces rel=nofollow and strips allowed tags
		$quoted = array_map(static fn (string $s): string => preg_quote($s, '#'), self::ALLOWED_URL_SCHEMES);
		$schemePattern = '#(?:' . implode('|', $quoted) . '):#Ai';
		$texy->urlSchemeFilters[Texy::FILTER_ANCHOR] = $schemePattern;
		$texy->urlSchemeFilters[Texy::FILTER_IMAGE] = $schemePattern;
		$texy->addHandler('phrase', $this->phraseHandler->solve(...));
		return $texy;
	}


	private function getTexyNoLongWords(): Texy
	{
		if ($this->texyNoLongWords === null) {
			$this->texyNoLongWords = $this->createTexy();
			$this->texyNoLongWords->allowed['longwords'] = false;
		}
		return $this->texyNoLongWords;
	}


	public function getTexy(): Texy
	{
		if ($this->texy === null) {
			$this->texy = $this->createTexy();
		}
		return $this->texy;
	}


	/**
	 * Passes args through Texy, so `link:`/URLs/emails in args turn into <a> tags.
	 * Only safe when args are developer- or admin-controlled. For user-controlled args use substitute().
	 *
	 * @param list<string|Stringable|int> $args
	 */
	public function substitutePossiblyUnsafeHtml(string|Stringable $format, array $args): Html
	{
		array_walk($args, function (string|Stringable|int &$value): void {
			$value = (string)$value;
		});
		return $this->format(vsprintf((string)$format, $args));
	}


	/**
	 * Treats each arg as literal text - args are HTML-escaped and never interpreted as Texy markup.
	 * Safe for user-controlled args. For developer/admin-controlled Texy markup use substitutePossiblyUnsafeHtml().
	 *
	 * Format string must use %s placeholders only. The marker scheme replaces %s slots with opaque
	 * marker strings before vsprintf runs; type-specific specifiers (%d, %f, etc.) would coerce
	 * the marker (yielding 0 / 0.0) and strtr would no longer find the marker to restore the real
	 * value, silently dropping the arg. Use (string)$arg at the call site and %s in the translation.
	 *
	 * @param list<string|Stringable|int> $args
	 */
	public function substitute(string|Stringable $format, array $args): Html
	{
		$formatString = (string)$format;
		// Texy::protect() can't be used: Texy::process() clears $this->marks
		// No : in prefix to avoid matching format placeholders (KEY:value)
		$markerPrefix = 'TEXY_FORMAT_ARG_' . Hash::nonCryptographic($formatString);
		$markers = [];
		$replacements = [];
		foreach ($args as $i => $arg) {
			$marker = $markerPrefix . '#' . $i;
			$markers[] = $marker;
			$replacements[$marker] = htmlspecialchars((string)$arg);
		}
		$html = (string)$this->withTexy($this->getTexyNoLongWords())->format(vsprintf($formatString, $markers));
		return Html::el()->setHtml(strtr($html, $replacements));
	}


	/**
	 * Passes replacements through Texy, so `link:`/URLs/emails turn into <a> tags.
	 * Only safe when replacements are developer- or admin-controlled. For user-controlled replacements use translate().
	 *
	 * @param list<string|Stringable|int> $replacements
	 * @throws InvalidArgument
	 */
	public function translatePossiblyUnsafeHtml(string $message, array $replacements = []): Html
	{
		return $this->substitutePossiblyUnsafeHtml($this->translator->translate($message), $replacements);
	}


	/**
	 * Treats each replacement as literal text - replacements are HTML-escaped and never interpreted as Texy markup.
	 * Safe for user-controlled replacements. For developer/admin-controlled Texy markup use translatePossiblyUnsafeHtml().
	 *
	 * @param list<string|Stringable|int> $replacements
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
		$texy = $this->getTexy();
		return $this->replace($text . self::CACHE_KEY_DELIMITER . __FUNCTION__, $texy, function () use ($texy, $text): string {
			return Regex::replace('~^\s*<p[^>]*>(.*)</p>\s*$~s', '$1', $texy->process($text))->result;
		});
	}


	/**
	 * Format string.
	 */
	public function formatBlock(string $text): Html
	{
		$texy = $this->getTexy();
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

		$result = Regex::replaceCallbackStrictGroups(
			'~\*\*([^:]+):([^*]+)\*\*~',
			/** @param array<int, string> $matches */
			function (array $matches) use ($replacements): string {
				return (isset($replacements[$matches[1]]) ? $replacements[$matches[1]]($matches[2]) : '');
			},
			$result,
		)->result;
		return Html::el()->setHtml($result);
	}


	public function getCacheKey(string $text, Texy $texy): string
	{
		// Anything that varies per render and influences the output goes in the key
		$key = serialize([
			$text,
			$texy->allowedTags,
			$texy->allowed,
			$texy->urlSchemeFilters,
			$texy->headingModule->top,
			$texy->obfuscateEmail,
			$texy->imageModule->root,
			$texy->imageModule->fileRoot,
		]);
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
		return clone($this, ['texy' => $texy]);
	}

}
