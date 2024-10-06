<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity;

enum HtmlElement: string
{

	case Link = 'link';
	case Script = 'script';


	public function hasEndTag(): bool
	{
		return match ($this) {
			self::Link => false,
			self::Script => true,
		};
	}


	public function getCommonFileExtension(): string
	{
		return match ($this) {
			self::Link => 'css',
			self::Script => 'js',
		};
	}

}
