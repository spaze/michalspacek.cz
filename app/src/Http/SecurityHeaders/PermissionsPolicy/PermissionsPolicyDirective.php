<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Http\SecurityHeaders\PermissionsPolicy;

enum PermissionsPolicyDirective: string
{

	case Accelerometer = 'accelerometer';
	case Camera = 'camera';
	case Geolocation = 'geolocation';
	case Gyroscope = 'gyroscope';
	case Magnetometer = 'magnetometer';
	case Microphone = 'microphone';
	case Midi = 'midi';
	case Payment = 'payment';
	case Usb = 'usb';

}
