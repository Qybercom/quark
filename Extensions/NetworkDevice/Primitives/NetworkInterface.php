<?php
namespace Quark\Extensions\NetworkDevice\Primitives;

use Quark\QuarkModelBehavior;

use Quark\Extensions\NetworkDevice\INetworkDevicePrimitive;

/**
 * Class NetworkInterface
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property bool $running
 * @property bool $disabled
 * @property bool $slave
 * @property string $ip
 * @property string $mac
 * @property int $mtu
 * @property string $speed
 * @property int $rx_bytes
 * @property int $rx_packets
 * @property int $tx_bytes
 * @property int $tx_packets
 * @property string $comment
 *
 * @package Quark\Extensions\NetworkDevice\Primitives
 */
class NetworkInterface implements INetworkDevicePrimitive {
	const TYPE_ETHERNET = 'ethernet';
	const TYPE_BRIDGE = 'bridge';
	const TYPE_6TO4_TUNNEL = '6to4-tunnel';
	const TYPE_L2TP_IN = 'l2tp-in';
	const TYPE_L2TP_OUT = 'l2tp-out';
	const TYPE_VLAN = 'vlan';
	const TYPE_WIFI = 'wifi';
	const TYPE_WIREGUARD = 'wireguard';
	const TYPE_UNKNOWN = '';
	// TODO: add other types
	
	use QuarkModelBehavior;
	
	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'id' => $this->Nullable(''),
			'name' => $this->Nullable(''),
			'type' => $this->Nullable(self::TYPE_UNKNOWN),
			'running' => $this->Nullable(false),
			'disabled' => $this->Nullable(false),
			'slave' => $this->Nullable(false),
			'ip' => $this->Nullable(''),
			'mac' => $this->Nullable(''),
			'mtu' => $this->Nullable(0),
			'speed' => $this->Nullable(''),
			'rx_bytes' => $this->Nullable(0),
			'rx_packets' => $this->Nullable(0),
			'tx_bytes' => $this->Nullable(0),
			'tx_packets' => $this->Nullable(0),
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