<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use Nette\Utils\Html;

final readonly class DependencyVersion
{

	public function __construct(
		private string $version,
		private string $reference,
	) {
	}


	public function getVersion(): string
	{
		return $this->version;
	}


	public function getReference(): string
	{
		return $this->reference;
	}


	public function getFullVersion(): string
	{
		return $this->version . '@' . $this->reference;
	}


	public function getFullVersionHtml(): Html
	{
		return Html::el('code')
			->setText($this->version)
			->addHtml(Html::el('small')->setText('@')->addText($this->reference));
	}


	public function equals(self $version): bool
	{
		return $this->getFullVersion() === $version->getFullVersion();
	}

}
