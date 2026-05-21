<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use Composer\Pcre\Regex;
use Exception;
use MichalSpacekCz\Form\Validators\Exceptions\FormValidatorTexyFormatterErrorException;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Formatter\TexyPhraseHandler\Shortcuts\TexyShortcutLink;
use Nette\Application\UI\InvalidLinkException;
use Nette\Utils\Html;

final readonly class FormValidatorTexyFormatter
{

	private const array CUSTOM_URL_SCHEMES = [TexyShortcutLink::SCHEME];


	public function __construct(
		private TexyFormatter $texyFormatter,
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
		// Catch at input what createTexy() would silently drop at render
		$allowedSchemes = [...TexyFormatter::ALLOWED_URL_SCHEMES, ...self::CUSTOM_URL_SCHEMES];
		$quoted = array_map(static fn (string $s): string => preg_quote($s, '~'), $allowedSchemes);
		$pattern = '~
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
			(?! (?: ' . implode('|', $quoted) . ' ): )
			( [a-z] [a-z0-9+.-]* ) :
		~imx';
		$schemeMatch = Regex::match($pattern, $value);
		if ($schemeMatch->matched && isset($schemeMatch->matches[1])) {
			throw new FormValidatorTexyFormatterErrorException(
				sprintf("URL scheme '%s' is not allowed in Texy links; allowed: %s", $schemeMatch->matches[1], implode(', ', $allowedSchemes)),
			);
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

}
