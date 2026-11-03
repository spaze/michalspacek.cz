<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Parser\FieldProcessors;

use Override;
use Spaze\SecurityTxt\Exceptions\SecurityTxtError;
use Spaze\SecurityTxt\SecurityTxt;
use Spaze\SecurityTxt\Violations\SecurityTxtMultipleBugBounty;

final class BugBountyCheckMultipleFields implements FieldProcessor
{

	/**
	 * The file the last Bug-Bounty field was in, because the `SecurityTxt` object has no Bug-Bounty value after one whose value is neither True nor False
	 */
	private ?SecurityTxt $securityTxt = null;


	/**
	 * @throws SecurityTxtError
	 */
	#[Override]
	public function process(string $value, SecurityTxt $securityTxt): void
	{
		if ($this->securityTxt === $securityTxt) {
			throw new SecurityTxtError(new SecurityTxtMultipleBugBounty());
		}
		$this->securityTxt = $securityTxt;
	}

}
