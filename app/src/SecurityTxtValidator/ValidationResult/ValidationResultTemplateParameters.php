<?php
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use DateTimeImmutable;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtIssue;
use MichalSpacekCz\SecurityTxtValidator\Issue\SecurityTxtLineIssue;
use MichalSpacekCz\SecurityTxtValidator\LayoutTemplateParameters;
use Nette\Utils\Html;
use Spaze\SecurityTxt\Signature\SecurityTxtSignatureVerifyResult;

final class ValidationResultTemplateParameters extends LayoutTemplateParameters
{

	public ?string $cacheTtl = null;
	public ?string $host = null;
	public ?DateTimeImmutable $downloadedAt = null;
	public ?string $downloadedAgo = null;
	public ?bool $fileExists = null;
	public ?bool $isValid = null;
	public ?bool $isValidWithWarnings = null;
	public ?bool $isInvalid = null;
	public ?Html $errorMessage = null;
	public ?int $expiresInDays = null;
	public ?Html $contents = null;
	public bool $isTruncated = false;
	public ?SecurityTxtSignatureVerifyResult $signed = null;
	public ?string $url = null;
	public ?Html $displayUrl = null;

	/** @var list<SecurityTxtIssue> $fetchErrors */
	public array $fetchErrors = [];

	/** @var list<SecurityTxtIssue> $fetchWarnings */
	public array $fetchWarnings = [];

	/** @var array<int, list<SecurityTxtLineIssue>> $lineIssues */
	public array $lineIssues = [];

	/** @var list<SecurityTxtIssue> $fileErrors */
	public array $fileErrors = [];

	/** @var list<SecurityTxtIssue> $fileWarnings */
	public array $fileWarnings = [];

	/** @var array<string, list<string>> $allRedirects */
	public array $allRedirects = [];

}
