<?php
namespace Quark\Extensions\NetworkDevice\Primitives;

use Quark\QuarkDate;
use Quark\QuarkModelBehavior;

use Quark\Extensions\NetworkDevice\INetworkDevicePrimitive;

/**
 * Class DHCPServerLease
 *
 * @property string $id
 * @property string $version
 * @property string $ip
 * @property string $mac
 * @property string $clientID
 * @property string $server
 * @property string $status
 * @property bool $dynamic
 * @property bool $disabled
 * @property string $hostname
 * @property QuarkDate $expiration
 * @property string $comment
 *
 * @package Quark\Extensions\NetworkDevice\Primitives
 */
class DHCPServerLease implements INetworkDevicePrimitive {
	const STATUS_WAITING = 'waiting';
	const STATUS_OFFERED = 'offered';
	const STATUS_BOUND = 'bound';
	const STATUS_RELEASED = 'released';
	const STATUS_EXPIRED = 'expired';
	const STATUS_UNKNOWN = '';
	
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => $this->Nullable(''),
			'version' => $this->Nullable(''),
			'ip' => $this->Nullable(''),
			'mac' => $this->Nullable(''),
			'clientID' => $this->Nullable(''),
			'server' => $this->Nullable(''),
			'status' => $this->Nullable(self::STATUS_UNKNOWN),
			'dynamic' => $this->Nullable(true),
			'disabled' => $this->Nullable(false),
			'hostname' => $this->Nullable(''),
			'expiration' => $this->Nullable(QuarkDate::NowUTC()),
			'comment' => $this->Nullable('')
		);
	}
	
	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}
}