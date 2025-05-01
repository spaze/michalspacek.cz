<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\Fetch;

use Override;
use Spaze\SvgIcons\SvgIcons;
use Tracy\IBarPanel;

final readonly class SecurityTxtValidatorFetchTracyPanel implements IBarPanel
{

	public function __construct(
		private SecurityTxtValidatorFetch $validatorFetch,
		private SvgIcons $svgIcons,
	) {
	}


	#[Override]
	public function getTab(): string
	{
		$type = match ($this->validatorFetch::class) {
			SecurityTxtValidatorLambdaFetch::class => 'AWS λ fetch',
			SecurityTxtValidatorDirectFetch::class => 'Direct fetch',
			default => 'Unknown fetch',
		};
		return '<span title="security.txt validator">'
			. trim($this->svgIcons->getSvg('certificate'))
			. '<span class="tracy-label">' . htmlspecialchars($type) . '</span>'
			. '</span>';
	}


	#[Override]
	public function getPanel(): string
	{
		$names = explode('\\', $this->validatorFetch::class);
		$class = implode('\\', [...array_slice($names, 0, -1), '<strong>' . $names[array_key_last($names)] . '</strong>']);
		return '<h1>security.txt validator fetcher</h1>'
			. '<div class="tracy-inner">'
			. '<div class="tracy-inner-container">'
			. 'Class <code>' . $class . '</code>'
			. '</div>'
			. '</div>';
	}

}
