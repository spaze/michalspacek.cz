<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application\Locale;

use Attribute;

/**
 * Leaves the language switcher out of the action's page, for the reason given. For a page that can't be shown in the
 * other language, like one showing what the visitor's session holds, which the other language's host doesn't have,
 * where the switcher would lead to an error.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class NoLanguageSwitcher
{

	public function __construct(
		public string $reason,
	) {
	}

}
