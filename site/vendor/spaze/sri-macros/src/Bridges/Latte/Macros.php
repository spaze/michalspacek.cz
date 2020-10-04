<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Bridges\Latte;

use Spaze\SubresourceIntegrity\Exceptions;
use Spaze\SubresourceIntegrity\FileBuilder;

class Macros
{

	/** @var \Spaze\SubresourceIntegrity\Config */
	private $sriConfig;


	/**
	 * Constructor.
	 *
	 * @param \Spaze\SubresourceIntegrity\Config $sriConfig
	 */
	public function __construct(\Spaze\SubresourceIntegrity\Config $sriConfig)
	{
		$this->sriConfig = $sriConfig;
	}


	/**
	 * Install macros.
	 *
	 * @param \Latte\Compiler $compiler
	 * @return \Latte\Macros\MacroSet
	 */
	public function install(\Latte\Compiler $compiler): \Latte\Macros\MacroSet
	{
		$set = new \Latte\Macros\MacroSet($compiler);
		$set->addMacro('script', array($this, 'macroScript'));
		$set->addMacro('stylesheet', array($this, 'macroStylesheet'));
		$set->addMacro('resourceurl', array($this, 'macroResourceUrl'));
		$set->addMacro('resourcehash', array($this, 'macroResourceHash'));
		return $set;
	}


	/**
	 * {script ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroScript(\Latte\MacroNode $node, \Latte\PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new Exceptions\ShouldNotHappenException();
		}

		$url = $this->sriConfig->getUrl($resource, FileBuilder::EXT_JS);
		$hash = $this->sriConfig->getHash($resource, FileBuilder::EXT_JS);

		return $writer->write(
			"echo '<script"
			. " src=\"' . %escape('" . $url . "') . '\""
			. " integrity=\"' . %escape('" . $hash . "') . '\""
			. "' . (isset(\$this->global->nonceGenerator) && \$this->global->nonceGenerator instanceof \\Spaze\\NonceGenerator\\GeneratorInterface ? ' nonce=\"' . %escape(\$this->global->nonceGenerator->getNonce()) . '\"' : '')"
			. $this->buildAttributes('script', $node)
			. " . '></script>';"
		);
	}


	/**
	 * {stylesheet ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroStylesheet(\Latte\MacroNode $node, \Latte\PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new Exceptions\ShouldNotHappenException();
		}

		return $writer->write(
			"echo '<link rel=\"stylesheet\""
			. " href=\"' . %escape('" . $this->sriConfig->getUrl($resource, FileBuilder::EXT_CSS) . "') . '\""
			. " integrity=\"' . %escape('" . $this->sriConfig->getHash($resource, FileBuilder::EXT_CSS) . "') . '\""
			. "' . (isset(\$this->global->nonceGenerator) && \$this->global->nonceGenerator instanceof \\Spaze\\NonceGenerator\\GeneratorInterface ? ' nonce=\"' . %escape(\$this->global->nonceGenerator->getNonce()) . '\"' : '')"
			. $this->buildAttributes('stylesheet', $node)
			. " . '>';"
		);
	}


	/**
	 * {resourceurl ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroResourceUrl(\Latte\MacroNode $node, \Latte\PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new Exceptions\ShouldNotHappenException();
		}

		return $writer->write("echo %escape('" . $this->sriConfig->getUrl($resource) . "');");
	}


	/**
	 * {resourcehash ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroResourceHash(\Latte\MacroNode $node, \Latte\PhpWriter $writer): string
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		if (!$resource) {
			throw new Exceptions\ShouldNotHappenException();
		}

		return $writer->write("echo %escape('" . $this->sriConfig->getHash($resource) . "');");
	}


	/**
	 * Build attributes.
	 *
	 * @param string $macro
	 * @param \Latte\MacroNode $node
	 * @return string
	 */
	private function buildAttributes($macro, \Latte\MacroNode $node): string
	{
		$attributes = array("'crossorigin'" => "'anonymous'");
		$isAttrName = true;
		$attrName = $attrValue = null;
		while ($node->tokenizer->nextToken()) {
			if ($node->tokenizer->isCurrent(\Latte\MacroTokens::T_SYMBOL)) {
				$value = "'{$node->tokenizer->currentValue()}'";
				$isAttrName ? $attrName = $value : $attrValue = $value;
			} elseif ($node->tokenizer->isCurrent(\Latte\MacroTokens::T_STRING, \Latte\MacroTokens::T_VARIABLE)) {
				$value = $node->tokenizer->currentValue();
				$isAttrName ? $attrName = $value : $attrValue = $value;
			} elseif ($node->tokenizer->isCurrent('=', '=>')) {
				$isAttrName = false;
			} elseif ($node->tokenizer->isCurrent(',')) {
				$attributes[$attrName] = ($attrValue ?: null);
				$isAttrName = true;
				$attrName = $attrValue = null;
			} elseif (!$node->tokenizer->isCurrent(\Latte\MacroTokens::T_WHITESPACE)) {
				throw new \Latte\CompileException("Unexpected '{$node->tokenizer->currentValue()}' in {{$macro} {$node->args}}");
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
