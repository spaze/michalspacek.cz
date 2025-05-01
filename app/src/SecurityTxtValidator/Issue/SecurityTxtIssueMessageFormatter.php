<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Issue;

use Nette\Utils\Html;

final readonly class SecurityTxtIssueMessageFormatter
{

	/**
	 * @param list<string> $values
	 */
	public function format(string $format, array $values): Html
	{
		$el = Html::el();
		$parts = explode('%s', $format);
		$count = count($values);
		for ($i = 0; $i < $count; $i++) {
			$el->addText($parts[$i]);
			$el->addHtml(Html::el('code')->setText($values[$i]));
		}
		$el->addText($parts[$count]);
		return $el;
	}

}
