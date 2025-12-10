<?php
declare(strict_types = 1);

namespace Spaze\SecurityTxt\Signature;

final readonly class SecurityTxtSignatureErrorInfo
{

	public function __construct(
		private string|false|null $message,
		private ?int $code,
		private ?string $source,
		private ?string $libraryMessage,
	) {
	}


	public function getMessage(): string|false|null
	{
		return $this->message;
	}


	public function getMessageAsString(): string
	{
		return $this->message === false ? '<false>' : ($this->message === null ? '<null>' : $this->message);
	}


	public function getCode(): ?int
	{
		return $this->code;
	}


	public function getCodeAsString(): string
	{
		return $this->code !== null ? (string)$this->code : '<null>';
	}


	public function getSource(): ?string
	{
		return $this->source;
	}


	public function getSourceAsString(): string
	{
		return $this->source ?? '<null>';
	}


	public function getLibraryMessage(): ?string
	{
		return $this->libraryMessage;
	}


	public function getLibraryMessageAsString(): string
	{
		return $this->libraryMessage ?? '<null>';
	}

}
