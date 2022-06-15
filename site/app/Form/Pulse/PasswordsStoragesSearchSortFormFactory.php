<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Pulse;

use MichalSpacekCz\Form\UnprotectedFormFactory;
use MichalSpacekCz\Pulse\Passwords\PasswordsSorting;
use MichalSpacekCz\Pulse\Passwords\Rating;
use Nette\Application\UI\Form;

class PasswordsStoragesSearchSortFormFactory
{

	public function __construct(
		private readonly UnprotectedFormFactory $factory,
		private readonly Rating $rating,
		private readonly PasswordsSorting $sorting,
	) {
	}


	public function create(?string $rating, ?string $sort, ?string $search): Form
	{
		$form = $this->factory->create();
		$form->setMethod('get');
		$items = ['all' => 'all'] + $this->rating->getRatings();
		$form->addSelect('rating', 'Rating:', $items)->setDefaultValue($rating && array_key_exists($rating, $items) ? $rating : 'all');
		$sorting = $this->sorting->getSorting();
		$form->addSelect('sort', 'Sort by:', $sorting)->setDefaultValue($sort && array_key_exists($sort, $sorting) ? $sort : array_key_first($sorting));
		$placeholder = 'company, site, disclosure';
		$form->addText('search', 'Search:')
			->setHtmlAttribute('placeholder', $placeholder)
			->setHtmlAttribute('title', $placeholder)
			->setHtmlType('search')
			->setDefaultValue($search);
		$form->onSuccess[] = function (): void {
			// Intentionally empty, the form values are passed to the action as method params.
			// Values can also be passed directly in the URL, not via the form, so the form doesn't need any onSuccess handler.
		};
		return $form;
	}

}
