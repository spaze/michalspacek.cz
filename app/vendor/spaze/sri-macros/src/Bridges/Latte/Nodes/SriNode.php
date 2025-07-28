<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte\Nodes;

use Generator;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\Position;
use Latte\Compiler\PrintContext;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\HtmlElement;

abstract class SriNode extends StatementNode
{

	/**
	 * @param array<int, string> $resources
	 * @param array<string, string|null> $attributes
	 */
	final public function __construct(
		protected Config $sriConfig,
		protected array $resources,
		private array $attributes,
	) {
	}


	/**
	 * @param array<string, string|null> $attributes
	 */
	protected function printTag(PrintContext $context, ?Position $position, array $attributes, ?HtmlElement $targetHtmlElement = null): string
	{
		$mask = <<<'XX'
			echo '<' . %escape(%dump) %line;
			$ʟ__sriTagAttrs = %dump;
			if (isset($this->global->uiNonce)) {
				$ʟ__sriTagAttrs['nonce'] = (string)$this->global->uiNonce;
			}
			foreach ($ʟ__sriTagAttrs as $ʟ__sriTagAttrKey => $ʟ__sriTagAttrValue) {
				echo $ʟ__sriTagAttrValue ? ' ' . %escape($ʟ__sriTagAttrKey) . '="' . %escape($ʟ__sriTagAttrValue) . '"' : ' ' . %escape($ʟ__sriTagAttrKey);
			}
			echo '>';
			XX;
		if ($targetHtmlElement?->hasEndTag()) {
			$mask .= <<<'XX'
			echo '</' . %escape(%dump) . '>';
			XX;
		}
		return $context->format(
			$mask,
			$targetHtmlElement?->value,
			$position,
			$attributes + $this->attributes,
			$targetHtmlElement?->value,
		);
	}


	/**
	 * @noinspection PhpInconsistentReturnPointsInspection
	 */
	public function &getIterator(): Generator
	{
		/**
		 * @noinspection PhpBooleanCanBeSimplifiedInspection
		 * @phpstan-ignore generator.valueType, booleanAnd.leftAlwaysFalse
		 */
		false && yield;
	}

}
