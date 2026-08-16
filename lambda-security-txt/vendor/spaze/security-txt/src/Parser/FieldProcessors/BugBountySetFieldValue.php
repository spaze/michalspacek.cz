<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\Fields\SecurityTxtBugBounty;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtBugBountyWrongCase;
use Spaze\SecurityTxt\Violations\SecurityTxtBugBountyWrongValue;

final class BugBountySetFieldValue implements FieldProcessor
{

	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($value === 'True') {
			$reward = true;
		} elseif ($value === 'False') {
			$reward = false;
		} elseif ($value === 'true' || $value === 'false') {
			throw new SecurityTxtError(new SecurityTxtBugBountyWrongCase($value));
		} else {
			throw new SecurityTxtError(new SecurityTxtBugBountyWrongValue($value));
		}
		$bugBounty = new SecurityTxtBugBounty($reward);
		$securityTxt->setBugBounty($bugBounty);
	}

}
