<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Composer\Pcre\Regex;
use Exception;
use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts\TexyShortcut;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;

final readonly class FormValidatorTexyFormatter
{

	private const string URL_SLOT_PATTERN = '~
		(?:
			\[ \* \s*                          # [* URL ...   (inline image source)
			|
			\[                                 # [URL] / [ref]   (link URL slot or reference use)
			|
			":                                 # "label":URL   (labeled link)
			|
			^ \[ [^\]\n]{1,100} \]: \ +        # [ref]: URL   (link or image ref-def at line start)
			|
			\b (?= ftp:// )                    # bare ftp://x text   (only auto-detected non-http(s) scheme)
			|
			[*<>] \]:                          # *]:URL, >]:URL, <]:URL   (inline image anchor; Texy allows *, >, < close modifiers)
		)
		\s*
		( [a-z] [a-z0-9+.-]* ) :
	~imx';


	/**
	 * @param list<TexyShortcut> $shortcuts
	 */
	public function __construct(
		private TexyFormatter $texyFormatter,
		private array $shortcuts,
	) {
	}


	/**
	 * @throws FormValidatorTexyFormatterErrorException
	 */
	public function format(mixed $value): ?Html
	{
		if (!is_string($value)) {
			return null;
		}
		// Catch at input what createTexy() would silently drop at render.
		$matches = Regex::matchAllStrictGroups(self::URL_SLOT_PATTERN, $value);
		foreach ($matches->matches[1] as $scheme) {
			if (!$this->isAllowedScheme($scheme)) {
				throw new FormValidatorTexyFormatterErrorException("URL scheme '{$scheme}' is not allowed in Texy URLs, links, or images");
			}
		}
		try {
			// Use a fresh Texy instance to avoid stale internal status throwing "Processing is in progress" exception on next Texy render.
			// It's ok to format the same input multiple times, because TexyFormatter caches the output and uses the cache when needed.
			$texyFormatter = $this->texyFormatter->withTexy($this->texyFormatter->createTexy());
			return $texyFormatter->format($value);
		} catch (Exception $e) {
			$prefix = $e instanceof InvalidLinkException ? 'Invalid link' : $e::class;
			throw new FormValidatorTexyFormatterErrorException("$prefix: {$e->getMessage()}", previous: $e);
		}
	}


	/**
	 * Scheme is allowed if it's in the static allowlist, or if any registered TexyShortcut
	 * claims it via canResolve() - so new shortcuts auto-extend the allowlist.
	 */
	public function isAllowedScheme(string $scheme): bool
	{
		$lower = strtolower($scheme);
		if (in_array($lower, TexyFormatter::ALLOWED_URL_SCHEMES, true)) {
			return true;
		}
		foreach ($this->shortcuts as $shortcut) {
			if ($shortcut->canResolve($lower . ':')) {
				return true;
			}
		}
		return false;
	}

}
