<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Training\ApplicationStatuses;

enum TrainingApplicationStatus: string
{

	case Created = 'CREATED'; // 1
	case Tentative = 'TENTATIVE'; // 2
	case Invited = 'INVITED'; // 3
	case SignedUp = 'SIGNED_UP'; // 4
	case InvoiceSent = 'INVOICE_SENT'; // 5
	case Notified = 'NOTIFIED'; // 6
	case Attended = 'ATTENDED'; // 7
	case MaterialsSent = 'MATERIALS_SENT'; // 8
	case AccessTokenUsed = 'ACCESS_TOKEN_USED'; // 9
	case Canceled = 'CANCELED'; // 10
	case Refunded = 'REFUNDED'; // 11
	case Credit = 'CREDIT'; // 12
	case Imported = 'IMPORTED'; // 13
	case NonPublicTraining = 'NON_PUBLIC_TRAINING'; // 14
	case Reminded = 'REMINDED'; // 15
	case PaidAfter = 'PAID_AFTER'; // 16
	case InvoiceSentAfter = 'INVOICE_SENT_AFTER'; // 17
	case ProFormaInvoiceSent = 'PRO_FORMA_INVOICE_SENT'; // 18
	case Spam = 'SPAM'; // 19

}
