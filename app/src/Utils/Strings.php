<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Utils;

use Composer\Pcre\Preg;
use Contributte\Translation\Translator;
use Nette\Utils\Html;

final readonly class Strings
{

	public function __construct(
		private Translator $translator,
	) {
	}


	/**
	 * Get initial letter because sometimes it might be two letters.
	 */
	public function getInitialLetterUppercase(string $string): string
	{
		$length = ($this->translator->getDefaultLocale() === 'cs_CZ' && mb_strtolower(mb_substr($string, 0, 2)) === 'ch' ? 2 : 1);
		$initial = mb_substr($string, 0, $length);
		return mb_strtoupper(mb_substr($initial, 0, 1)) . mb_strtolower(mb_substr($initial, 1));
	}


	/**
	 * Get string length in characters.
	 * Similar to Nette\Utils\Strings::length() but doesn't check for the mbstring extension availability.
	 */
	public function length(string $string): int
	{
		return mb_strlen($string);
	}


	public function addLineNumbersAndEolChars(string $string, ?string $cssClassLines = null, ?string $cssClassLineNumbers = null, ?string $cssClassEol = null): Html
	{
		$el = Html::el();
		$lineNr = 1;
		foreach (Preg::split("/(?<=\n)/", $string) as $line) {
			if ($line === '') {
				continue;
			}
			$lineSpan = Html::el('span')->class($cssClassLines);
			$lineSpan->addHtml(Html::el('span')->setText($lineNr++)->addText(' ')->class($cssClassLineNumbers));
			$lineSpan->addText(trim($line));
			$eolSpan = Html::el('span')->class($cssClassEol);
			$eol = null;
			if (str_ends_with($line, "\r\n")) {
				$eol = "\r\n";
				$lineSpan->addHtml($eolSpan->addText('<CRLF>'));
			} elseif (str_ends_with($line, "\n")) {
				$eol = "\n";
				$lineSpan->addHtml($eolSpan->addText('<LF>'));
			}
			$el->addHtml($lineSpan);
			if ($eol !== null) {
				$el->addText($eol);
			}
		}
		return $el;
	}


	public function addWordBreaks(string $string): Html
	{
		$parts = explode('.', $string);
		$count = count($parts);
		$el = Html::el()->addText($parts[0]);
		for ($i = 1; $i < $count; $i++) {
			if ($parts[$i - 1] !== '' && ($parts[$i] !== '' || isset($parts[$i + 1]))) {
				$el->addHtml(Html::el('wbr'));
			}
			$el->addText(".{$parts[$i]}");
		}
		return $el;
	}

}
