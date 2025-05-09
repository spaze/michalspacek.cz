<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use LogicException;
use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Fields\SecurityTxtPreferredLanguages;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtPreferredLanguagesSeparatorNotComma;

final class PreferredLanguagesSetFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		$regexp = '/\s*([,.;:])\s*/';
		$separators = preg_match_all($regexp, $value, $matches);
		$languages = @preg_split($regexp, $value); // intentionally @, converted to exception
		if ($languages === false) {
			throw new LogicException('This should not happen');
		}
		if ($separators !== false && $separators > 0) {
			$wrongSeparators = [];
			foreach ($matches[1] as $key => $separator) {
				if ($separator !== SecurityTxtPreferredLanguages::SEPARATOR) {
					$wrongSeparators[$key + 1] = $separator;
				}
			}
			if ($wrongSeparators !== []) {
				throw new SecurityTxtError(new SecurityTxtPreferredLanguagesSeparatorNotComma($wrongSeparators, $languages));
			}
		}
		$securityTxt->setPreferredLanguages(new SecurityTxtPreferredLanguages($languages));
	}

}
