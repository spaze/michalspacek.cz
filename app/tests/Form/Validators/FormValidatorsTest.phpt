<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Validators;

use MichalSpacekCz\Test\ComponentProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Forms\Controls\TextInput;
use Nette\Forms\Form;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class FormValidatorsTest extends TestCase
{

	public function __construct(
		private readonly FormValidators $validators,
	) {
	}


	/**
	 * @return list<array{0:string, 1:bool}>
	 */
	public function getSlugs(): array
	{
		return [
			['foo', true],
			['foo-bar', true],
			['foo-bar.baz', true],
			['foo-bar,baz', true],
			['foo-bar_baz', true],
			['foo-bar-1337', true],
			['foo/bar', false],
			['Foo', false],
		];
	}


	/** @dataProvider getSlugs */
	public function testAddValidateSlugRules(string $slug, bool $result): void
	{
		$input = new TextInput();
		$input->value = $slug;
		$this->validators->addValidateSlugRules($input);
		ComponentProperty::setParentAndName($input, new Form(), null);
		$input->validate();
		Assert::same($result ? [] : ['messages.forms.validateSlugParamsError'], $input->getErrors());
	}


	public function testAddValidateSlugRulesPattern(): void
	{
		$results = [];
		foreach (['foo/bar', 'foo,bar', 'Foo/bar'] as $slug) {
			$input = new TextInput();
			$input->value = $slug;
			$this->validators->addValidateSlugRules($input, '[a-z/]+');
			ComponentProperty::setParentAndName($input, new Form(), null);
			$input->validate();
			$results[$slug] = $input->getErrors();
		}
		$error = ['messages.forms.validateSlugParamsError'];
		Assert::same(['foo/bar' => [], 'foo,bar' => $error, 'Foo/bar' => $error], $results);
	}

}

TestCaseRunner::run(FormValidatorsTest::class);
