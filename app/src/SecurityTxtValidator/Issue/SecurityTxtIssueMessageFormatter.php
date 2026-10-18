<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use MichalSpacekCz\Utils\Strings;
use Nette\Utils\Html;
use Spaze\SecurityTxt\SecurityTxtHost;
use Spaze\SecurityTxt\SecurityTxtPrintableValue;
use Uri\WhatWg\Url;

final readonly class SecurityTxtIssueMessageFormatter
{

	public function __construct(
		private Strings $strings,
	) {
	}


	/**
	 * @param list<SecurityTxtHost|string|Url> $values
	 */
	public function format(string $format, array $values): Html
	{
		$el = Html::el();
		$parts = explode('%s', $format);
		$count = count($values);
		for ($i = 0; $i < $count; $i++) {
			$el->addText($parts[$i]);
			$el->addHtml(Html::el('code')->addHtml($this->strings->addTabMarkersAndNonBreakingSpaces(new SecurityTxtPrintableValue($values[$i])->render())));
		}
		$el->addText($parts[$count]);
		return $el;
	}

}
