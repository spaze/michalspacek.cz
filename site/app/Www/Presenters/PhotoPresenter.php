<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Formatter\Texy;

class PhotoPresenter extends BasePresenter
{

	private Texy $texyFormatter;


	public function __construct(Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$photos = array(
			array(
				'header' => $this->translator->translate('messages.photo.trademark.header'),
				'file' => 'michalspacek-trademark-400x268.jpg',
				'desc' => $this->texyFormatter->translate('messages.photo.trademark.desc', ['https://twitter.com/spazef0rze', 'https://www.facebook.com/spaze', 'https://about.me/lukashudecek']),
				'sizes' => array(
					'400×268' => 'michalspacek-trademark-400x268.jpg',
					'800×536' => 'michalspacek-trademark-800x536.jpg',
					'1600×1071' => 'michalspacek-trademark-1600x1071.jpg',
				),
			),
			array(
				'header' => $this->translator->translate('messages.photo.lecturer.header'),
				'file' => 'michalspacek-codecamp2015-400x268.jpg',
				'desc' => $this->texyFormatter->translate('messages.photo.lecturer.desc', ['http://codecamp.cz/', 'https://galerie.fotohavlin.cz/']),
				'sizes' => array(
					'400×268' => 'michalspacek-codecamp2015-400x268.jpg',
					'800×536' => 'michalspacek-codecamp2015-800x536.jpg',
					'1600×1071' => 'michalspacek-codecamp2015-1600x1071.jpg',
				),
			),
			array(
				'header' => $this->translator->translate('messages.photo.office.header'),
				'file' => 'michalspacek-serious-400x268.jpg',
				'desc' => $this->translator->translate('messages.photo.office.desc'),
				'sizes' => array(
					'400×268' => 'michalspacek-serious-400x268.jpg',
					'400×400' => 'michalspacek-serious-400x400.jpg',
				),
			),
			array(
				'header' => $this->translator->translate('messages.photo.talk.header'),
				'file' => 'michalspacek-webtop100-400x268.jpg',
				'desc' => $this->texyFormatter->translate('messages.photo.talk.desc', ['link:Www:Talks:talk technicke-chyby-vas-pripravi-o-penize-webtop100']),
				'sizes' => array(
					'400×268' => 'michalspacek-webtop100-400x268.jpg',
					'720×480' => 'michalspacek-webtop100-720x480.jpg',
				),
			),
		);
		$this->template->photos = $photos;
		$this->template->pageTitle  = $this->translator->translate('messages.title.photo');
	}

}
