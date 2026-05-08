<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy;

use MichalSpacekCz\Presentation\Www\BasePresenter;
use MichalSpacekCz\Test\Http\Response;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class PermissionsPolicyTest extends TestCase
{

	public function __construct(
		private readonly Response $httpResponse,
		private readonly Application $application,
		private readonly PermissionsPolicy $permissionsPolicy,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->httpResponse->reset();
	}


	public function testSetDefault(): void
	{
		$presenter = new class extends BasePresenter {
		};
		PrivateProperty::setValue($this->application, 'presenter', $presenter);
		$this->permissionsPolicy->set();
		Assert::same(
			'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), publickey-credentials-create=(), publickey-credentials-get=(), usb=()',
			$this->httpResponse->getHeaders()['permissions-policy'],
		);
	}


	public function testSetMultipleOriginsIncludingString(): void
	{
		$presenter = new class extends BasePresenter {

			public function addPermissionsPolicyPublic(PermissionsPolicyDirective $directive, PermissionsPolicyOrigin|string $origin): void
			{
				$this->addPermissionsPolicy($directive, $origin);
			}

		};
		$presenter->addPermissionsPolicyPublic(PermissionsPolicyDirective::PublicKeyCredentialsGet, PermissionsPolicyOrigin::Self);
		$presenter->addPermissionsPolicyPublic(PermissionsPolicyDirective::PublicKeyCredentialsGet, 'https://foo.example');
		$presenter->addPermissionsPolicyPublic(PermissionsPolicyDirective::PublicKeyCredentialsCreate, PermissionsPolicyOrigin::Self);
		$presenter->addPermissionsPolicyPublic(PermissionsPolicyDirective::PublicKeyCredentialsCreate, 'https://bar.example');
		PrivateProperty::setValue($this->application, 'presenter', $presenter);
		$this->permissionsPolicy->set();
		Assert::same(
			'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), midi=(), payment=(), publickey-credentials-create=(self "https://bar.example"), publickey-credentials-get=(self "https://foo.example"), usb=()',
			$this->httpResponse->getHeaders()['permissions-policy'],
		);
	}

}

TestCaseRunner::run(PermissionsPolicyTest::class);
