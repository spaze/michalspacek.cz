<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Test\Formatter;

use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Test\WillThrow;
use Nette\Utils\Html;
use Override;
use Texy\Texy;

final class TexyFormatterMock extends TexyFormatter
{

	use WillThrow;


	#[Override]
	public function createTexy(): Texy
	{
		$texy = parent::createTexy();
		$texy->allowed['longwords'] = false;
		return $texy;
	}


	#[Override]
	public function format(string $text): Html
	{
		$this->maybeThrow();
		return parent::format($text);
	}


	#[Override]
	public function formatBlock(string $text): Html
	{
		$this->maybeThrow();
		return parent::formatBlock($text);
	}

}
