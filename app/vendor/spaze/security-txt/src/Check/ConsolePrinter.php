<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Check;

/**
 * @internal Should be used only in the check host class
 */
final class ConsolePrinter
{

	private bool $colors = false;


	public function enableColors(): void
	{
		$this->colors = true;
	}


	public function info(string $message): void
	{
		$this->print($this->colorDarkGray('[Info]'), $message);
	}


	public function error(string $message): void
	{
		$this->print($this->colorRed('[Error]'), $message);
	}


	public function warning(string $message): void
	{
		$this->print($this->colorBold('[Warning]'), $message);
	}


	private function print(string $level, string $message): void
	{
		$message = str_replace("\n", "\n{$level} ", $message);
		echo "{$level} {$message}\n";
	}


	public function colorRed(string $message): string
	{
		return $this->color("\033[1;31m", $message);
	}


	public function colorGreen(string $message): string
	{
		return $this->color("\033[1;32m", $message);
	}


	public function colorDarkGray(string $message): string
	{
		return $this->color("\033[1;90m", $message);
	}


	public function colorBold(string $message): string
	{
		return $this->color("\033[1m", $message);
	}


	private function color(string $color, string $message): string
	{
		return sprintf('%s%s%s', $this->colors ? $color : '', $message, $this->colors ? "\033[0m" : '');
	}

}
