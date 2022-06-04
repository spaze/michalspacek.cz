<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte;

use Latte\CompileException;
use Latte\Compiler;
use Latte\MacroNode;
use Latte\Macros\MacroSet;
use Latte\MacroTokens;
use Latte\PhpWriter;
use Spaze\SubresourceIntegrity\Config;
use Spaze\SubresourceIntegrity\Exceptions\ShouldNotHappenException;
use Spaze\SubresourceIntegrity\FileBuilder;

class Macros
{

	public function __construct(
		private Config $sriConfig,
	) {
	}


	public function install(Compiler $compiler): MacroSet
	{
		$set = new MacroSet($compiler);
		$set->addMacro('script', [$this, 'macroScript']);
		$set->addMacro('stylesheet', [$this, 'macroStylesheet']);
		$set->addMacro('resourceurl', [$this, 'macroResourceUrl']);
		$set->addMacro('resourcehash', [$this, 'macroResourceHash']);
		return $set;
	}


	/**
	 * {script ...}
	 *
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public function macroScript(MacroNode $node, PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new ShouldNotHappenException();
		}

		$url = $this->sriConfig->getUrl($resource, FileBuilder::EXT_JS);
		$hash = $this->sriConfig->getHash($resource, FileBuilder::EXT_JS);

		$code = "echo '<script"
			. " src=\"' . %escape('" . $url . "') . '\""
			. " integrity=\"' . %escape('" . $hash . "') . '\""
			. "' . (isset(\$this->global->nonceGenerator) && \$this->global->nonceGenerator instanceof \\Spaze\\NonceGenerator\\GeneratorInterface ? ' nonce=\"' . %escape(\$this->global->nonceGenerator->getNonce()) . '\"' : '')"
			. $this->buildAttributes('script', $node)
			. " . '></script>';";
		return $writer->write($code);
	}


	/**
	 * {stylesheet ...}
	 *
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public function macroStylesheet(MacroNode $node, PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new ShouldNotHappenException();
		}

		$code = "echo '<link rel=\"stylesheet\""
			. " href=\"' . %escape('" . $this->sriConfig->getUrl($resource, FileBuilder::EXT_CSS) . "') . '\""
			. " integrity=\"' . %escape('" . $this->sriConfig->getHash($resource, FileBuilder::EXT_CSS) . "') . '\""
			. "' . (isset(\$this->global->nonceGenerator) && \$this->global->nonceGenerator instanceof \\Spaze\\NonceGenerator\\GeneratorInterface ? ' nonce=\"' . %escape(\$this->global->nonceGenerator->getNonce()) . '\"' : '')"
			. $this->buildAttributes('stylesheet', $node)
			. " . '>';";
		return $writer->write($code);
	}


	/**
	 * {resourceurl ...}
	 *
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public function macroResourceUrl(MacroNode $node, PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new ShouldNotHappenException();
		}

		return $writer->write("echo %escape('" . $this->sriConfig->getUrl($resource) . "');");
	}


	/**
	 * {resourcehash ...}
	 *
	 * @throws CompileException
	 * @throws ShouldNotHappenException
	 */
	public function macroResourceHash(MacroNode $node, PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new ShouldNotHappenException();
		}

		return $writer->write("echo %escape('" . $this->sriConfig->getHash($resource) . "');");
	}


	/**
	 * @throws CompileException
	 */
	private function buildAttributes(string $macro, MacroNode $node): string
	{
		$attributes = ["'crossorigin'" => "'anonymous'"];
		$isAttrName = true;
		$attrName = $attrValue = null;
		while ($node->tokenizer->nextToken()) {
			if ($node->tokenizer->isCurrent(MacroTokens::T_SYMBOL)) {
				$value = "'{$node->tokenizer->currentValue()}'";
				$isAttrName ? $attrName = $value : $attrValue = $value;
			} elseif ($node->tokenizer->isCurrent(MacroTokens::T_STRING, MacroTokens::T_VARIABLE)) {
				$value = $node->tokenizer->currentValue();
				$isAttrName ? $attrName = $value : $attrValue = $value;
			} elseif ($node->tokenizer->isCurrent('=', '=>')) {
				$isAttrName = false;
			} elseif ($node->tokenizer->isCurrent(',')) {
				$attributes[$attrName] = ($attrValue ?: null);
				$isAttrName = true;
				$attrName = $attrValue = null;
			} elseif (!$node->tokenizer->isCurrent(MacroTokens::T_WHITESPACE)) {
				throw new CompileException("Unexpected '{$node->tokenizer->currentValue()}' in {{$macro} {$node->args}}");
			}
			if (!$node->tokenizer->isNext()) {
				$attributes[$attrName] = ($attrValue ?: null);
			}
		}

		$attrCode = '';
		foreach ($attributes as $name => $value) {
			$attrCode .= " . ' ' . %escape(" . $name . ")";
			if ($value !== null) {
				$attrCode .= " . '=\"' . %escape(" . $value . ") . '\"'";
			}
		}

		return $attrCode;
	}

}
