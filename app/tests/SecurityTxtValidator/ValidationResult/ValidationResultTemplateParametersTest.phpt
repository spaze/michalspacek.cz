<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator\ValidationResult;

use DateTimeImmutable;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class ValidationResultTemplateParametersTest extends TestCase
{

	public function testStoresProperties(): void
	{
		$downloadedAt = new DateTimeImmutable('-3 days');
		$params = new ValidationResultTemplateParameters();
		$params->showIntro = true;
		$params->logoExtraCssClass = LogoExtraCssClass::Valid;
		$params->logoExtraIcon = LogoExtraIcon::CertificateCheck;
		$params->accountFeatureEnabled = true;
		$params->cacheTtl = '5 minutes';
		$params->host = 'host.example';
		$params->downloadedAt = $downloadedAt;
		$params->downloadedAgo = '3 minutes';
		$params->fileExists = true;
		$params->isValid = true;
		$params->isValidWithWarnings = false;
		$params->isInvalid = false;
		$params->errorMessage = null;
		$params->expiresInDays = 123;
		$params->contents = null;
		$params->isTruncated = false;
		$params->signed = null;
		$params->url = 'https://host.example/.well-known/security.txt';
		$params->displayUrl = null;
		$params->fetchErrors = [];
		$params->fetchWarnings = [];
		$params->lineIssues = [];
		$params->fileErrors = [];
		$params->fileWarnings = [];
		$params->allRedirects = [];

		Assert::true($params->showIntro);
		Assert::same(LogoExtraCssClass::Valid, $params->logoExtraCssClass);
		Assert::same(LogoExtraIcon::CertificateCheck, $params->logoExtraIcon);
		Assert::true($params->accountFeatureEnabled);
		Assert::same('5 minutes', $params->cacheTtl);
		Assert::same('host.example', $params->host);
		Assert::same($downloadedAt, $params->downloadedAt);
		Assert::same('3 minutes', $params->downloadedAgo);
		Assert::true($params->fileExists);
		Assert::true($params->isValid);
		Assert::false($params->isValidWithWarnings);
		Assert::false($params->isInvalid);
		Assert::null($params->errorMessage);
		Assert::same(123, $params->expiresInDays);
		Assert::null($params->contents);
		Assert::false($params->isTruncated);
		Assert::null($params->signed);
		Assert::same('https://host.example/.well-known/security.txt', $params->url);
		Assert::null($params->displayUrl);
		Assert::same([], $params->fetchErrors);
		Assert::same([], $params->fetchWarnings);
		Assert::same([], $params->lineIssues);
		Assert::same([], $params->fileErrors);
		Assert::same([], $params->fileWarnings);
		Assert::same([], $params->allRedirects);
	}

}

TestCaseRunner::run(ValidationResultTemplateParametersTest::class);
