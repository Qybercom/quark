<?php
namespace Quark\Extensions\NetworkDevice\Providers\Mikrotik;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkCollection;
use Quark\QuarkDate;
use Quark\QuarkDateInterval;
use Quark\QuarkKeyValuePair;
use Quark\QuarkModel;
use Quark\QuarkTCPNetworkTransport;
use Quark\QuarkURI;

use Quark\Extensions\NetworkDevice\INetworkDeviceProvider;
use Quark\Extensions\NetworkDevice\Primitives\NetworkInterface;
use Quark\Extensions\NetworkDevice\Primitives\DHCPServerLease;

/**
 * Class Mikrotik
 *
 * https://github.com/BenMenking/routeros-api/blob/master/routeros_api.class.php
 *
 * @package Quark\Extensions\NetworkDevice\Providers\Mikrotik
 */
class Mikrotik implements INetworkDeviceProvider {
	const RESULT_DONE = '!done';
	const RESULT_RE = '!re';
	const RESULT_TRAP = '!trap';
	const RESULT_FATAL = '!fatal';
	
	/**
	 * @var string[] $_resultError
	 */
	private static $_resultError = array(
		self::RESULT_RE,
		self::RESULT_TRAP,
		self::RESULT_FATAL
	);
	
	/**
	 * @var QuarkClient $_client
	 */
	private $_client;
	
	/**
	 * @var bool $_connected = false
	 */
	private $_connected = false;
	
	/**
	 * @var bool $_authorized = false
	 */
	private $_authorized = false;
	
	/**
	 * @var bool $_debug = false
	 */
	private $_debug = false;
	
	/**
	 * @param bool $debug = false
	 */
	public function __construct ($debug = false) {
		$this->Debug($debug);
	}
	
	/**
	 * @param bool $debug = false
	 *
	 * @return bool
	 */
	public function Debug ($debug = false) {
		if (func_num_args() != 0)
			$this->_debug = $debug;
		
		return $this->_debug;
	}
	
	/**
	 * @return bool
	 */
	public function NetworkDeviceConnected () {
		return $this->_connected;
	}
	
	/**
	 * @return bool
	 */
	public function NetworkDeviceAuthorized () {
		return $this->_authorized;
	}
	
	/**
	 * @param QuarkURI $uri
	 *
	 * @return bool
	 */
	public function NetworkDeviceConnect (QuarkURI $uri) {
		$_uri = 'tcp://' . $uri->host . ':' . $uri->port;

		$this->_client = new QuarkClient($_uri, new QuarkTCPNetworkTransport());

		$this->_client->TimeoutConnect(3);

		$this->_client->On(QuarkClient::EVENT_CONNECT, function (QuarkClient $client) use (&$uri) {
			$this->_connected = true;
		});

		$this->_client->On(QuarkClient::EVENT_CLOSE, function (QuarkClient $client) use (&$uri) {
			$this->_connected = false;
		});

		$this->_client->On(QuarkClient::EVENT_ERROR_CONNECT, function ($err) {
			Quark::Log('[Mikrotik:ErrorConnect] ' . $err, Quark::LOG_WARN);
		});

		$this->_client->On(QuarkClient::EVENT_ERROR_CRYPTOGRAM, function (QuarkClient $client, $err) {
			Quark::Log('[Mikrotik:ErrorCryptogram] ' . $err, Quark::LOG_WARN);
		});

		$this->_client->On(QuarkClient::EVENT_ERROR_PROTOCOL, function (QuarkClient $client, $err) {
			Quark::Log('[Mikrotik:ErrorProtocol] ' . $err, Quark::LOG_WARN);
		});

		return $this->_client->Connect();
	}

	/**
	 * @return bool
	 */
	public function NetworkDeviceClose () {
		return $this->_client->Close();
	}
	
	/**
	 * @param QuarkKeyValuePair $user
	 *
	 * @return bool
	 */
	public function NetworkDeviceAuthorize (QuarkKeyValuePair $user) {
		if (!$this->_connected) return false;
		
		$ok = $this->_send('/login', array(
			'name' => $user->Key(),
			'password' => $user->Value()
		));
		
		if (!$ok) return false;
		
		$this->_authorized = $this->_unserialize();
		
		return $this->_authorized;
	}

	/**
	 * @return QuarkCollection|NetworkInterface[]
	 */
	public function NetworkDeviceInterfaceList () {
		if (!$this->_connected || !$this->_authorized) return null;
		
		$ok = $this->_send('/interface/print'); // /interface ethernet monitor  // for speed
		if (!$ok) return array();
		
		$raw = $this->_unserialize();
		//print_r($raw);
		/**
			[.id] => string
			[name] => string
			[default-name] => string
			[type] => string
			[mtu] => int
			[actual-mtu] => int
			[l2mtu] => int
			[max-l2mtu] => int
			[mac-address] => 00:00:00:00:00:00
			[last-link-down-time] => date(MMM/dd/yyyy HH:mm:ss)
			[last-link-up-time] => date(MMM/dd/yyyy HH:mm:ss)
			[link-downs] => int
			[rx-byte] => int
			[tx-byte] => int
			[rx-packet] => int
			[tx-packet] => int
			[tx-queue-drop] => int
			[fp-rx-byte] => int
			[fp-tx-byte] => int
			[fp-rx-packet] => int
			[fp-tx-packet] => int
			[running] => boolable(false | true)
			[disabled] => boolable(false | true)
			[comment] => string
		 */
		
		/**
		 * @var QuarkCollection|NetworkInterface[] $out
		 */
		$out = new QuarkCollection(new NetworkInterface());
		$interface = null;
		
		foreach ($raw as $i => &$item) {
			/**
			 * @var QuarkModel|NetworkInterface $interface
			 */
			$interface = new QuarkModel(new NetworkInterface());
			
			if (isset($item['.id'])) $interface->id = $item['.id'];
			if (isset($item['name'])) $interface->name = $item['name'];
			if (isset($item['mac-address'])) $interface->mac = $item['mac-address'];
			//if (isset($item['mtu'])) $interface->mtu = (int)$item['mtu']; // not ok on some interfaces (disabled or wifi)
			if (isset($item['actual-mtu'])) $interface->mtu = (int)$item['actual-mtu'];
			if (isset($item['rx-byte'])) $interface->rx_bytes = (int)$item['rx-byte'];
			if (isset($item['rx-packet'])) $interface->rx_packets = (int)$item['rx-packet'];
			if (isset($item['tx-byte'])) $interface->tx_bytes = (int)$item['tx-byte'];
			if (isset($item['tx-packet'])) $interface->tx_packets = (int)$item['tx-packet'];
			if (isset($item['comment'])) $interface->comment = $item['comment'];
			
			if (isset($item['type'])) {
				if ($item['type'] == 'bridge') $interface->type = NetworkInterface::TYPE_BRIDGE;
				if ($item['type'] == 'ether') $interface->type = NetworkInterface::TYPE_ETHERNET;
				if ($item['type'] == 'wifi') $interface->type = NetworkInterface::TYPE_WIFI;
				if ($item['type'] == 'l2tp-in') $interface->type = NetworkInterface::TYPE_L2TP_IN;
				if ($item['type'] == 'l2tp-out') $interface->type = NetworkInterface::TYPE_L2TP_OUT;
				if ($item['type'] == 'wg') $interface->type = NetworkInterface::TYPE_WIREGUARD;
				if ($item['type'] == 'vlan') $interface->type = NetworkInterface::TYPE_VLAN;
				if ($item['type'] == '6to4-tunnel') $interface->type = NetworkInterface::TYPE_6TO4_TUNNEL;
			}
			
			if (isset($item['name'])) {
				if ($item['running'] == 'true') $interface->running = true;
				if ($item['running'] == 'false') $interface->running = false;
			}
			
			if (isset($item['name'])) {
				if ($item['disabled'] == 'true') $interface->disabled = true;
				if ($item['disabled'] == 'false') $interface->disabled = false;
			}
			
			if (isset($item['slave'])) {
				if ($item['slave'] == 'true') $interface->slave = true;
				if ($item['slave'] == 'false') $interface->slave = false;
			}
			
			$out[] = $interface;
		}
		
		unset($i, $item, $interface);
		
		return $out;
	}
	
	/**
	 * @return QuarkCollection|DHCPServerLease[]
	 */
	public function NetworkDeviceDHCPServerLeaseList ($ipVersion) {
		if (!$this->_connected || !$this->_authorized) return null;
		
		$ok = $this->_send('/ip/dhcp-server/lease/print');
		if (!$ok) return null;
		
		$raw = $this->_unserialize();
		//print_r($raw);
		/**
			[.id] => string
			[address] => 0.0.0.0
			[mac-address] => 00:00:00:00:00:00
			[client-id] => 1:00:00:00:00:00:00
			[address-lists] => ?
			[server] => string
			[dhcp-option] => ?
			[status] => string(bound | waiting | offered)
			[expires-after] => 00w00d00h0m00s
			[last-seen] => 00w00d00h0m00s
			[age] => 00w00d00h0m00s
			[active-address] => 0.0.0.0
			[active-mac-address] => 00:00:00:00:00:00
			[active-client-id] => 1:00:00:00:00:00:00
			[active-server] => string
			[host-name] => string
			[radius] => boolable(false | true)
			[dynamic] => boolable(false | true)
			[blocked] => boolable(false | true)
			[disabled] => boolable(false | true)
			[comment] => string
		 */
		
		// TODO: integrate following for obtain bridge-port
		// @see https://forum.mikrotik.com/viewtopic.php?t=182618#p972007
		/*
		:foreach lease in=[/ip dhcp-server lease find] do={
			:local mac [/ip dhcp-server lease get $lease mac-address];
			:local ip [/ip dhcp-server lease get $lease address];
			:local status [/ip dhcp-server lease get $lease status];
			:local iface "";   # Not Found
			:local bridgeName "";   # Not Found
			:local bridgePort "";   # Not Found   # new field for bridge-port
			:local outstring "undefined";
		
			# check table bridge host
			/interface bridge host
			:local searchresult [find where mac-address=$mac]
			:if ([:len $searchresult] > 0) do={
				:set iface [get $searchresult on-interface];
				:set bridgeName [get $searchresult bridge];
				:set bridgePort [get $searchresult interface];  # get bridge port
				:set outstring "For bridge host table, MAC $mac is coming from $iface on bridge $bridgeName port $bridgePort";
			} else={
				:set outstring "Not found in bridge host table for MAC $mac";
				# check ARP table
				/ip arp
				:set searchresult [find where mac-address=$mac]
				:if ([:len $searchresult] > 0) do={
					:set iface [get $searchresult interface];
					:set outstring "$outstring, but found in ARP table, coming from $iface";
				} else={
					:set outstring "$outstring, and also not found in ARP table.";
				}
			}
		
			# output like /ip dhcp-server lease print
			:put ("address=$ip mac-address=$mac status=$status bridge=$bridgeName bridge-port=$bridgePort");
		}
		 */
		
		/**
		 * @var QuarkCollection|DHCPServerLease[] $out
		 */
		$out = new QuarkCollection(new DHCPServerLease());
		$lease = null;
		
		foreach ($raw as $i => &$item) {
			/**
			 * @var QuarkModel|DHCPServerLease $lease
			 */
			$lease = new QuarkModel(new DHCPServerLease());
			$lease->version = $ipVersion;
			
			if (isset($item['.id'])) $lease->id = $item['.id'];
			if (isset($item['address'])) $lease->ip = $item['address'];
			if (isset($item['mac-address'])) $lease->mac = $item['mac-address'];
			if (isset($item['client-id'])) $lease->clientID = $item['client-id'];
			if (isset($item['server'])) $lease->server = $item['server'];
			if (isset($item['host-name'])) $lease->hostname = $item['host-name'];
			if (isset($item['comment'])) $lease->comment = $item['comment'];
			
			if (isset($item['dynamic'])) {
				if ($item['dynamic'] == 'true') $lease->dynamic = true;
				if ($item['dynamic'] == 'false') $lease->dynamic = false;
			}
			
			if (isset($item['disabled'])) {
				if ($item['disabled'] == 'true') $lease->disabled = true;
				if ($item['disabled'] == 'false') $lease->disabled = false;
			}
			
			if (isset($item['status'])) {
				if ($item['status'] == 'waiting') $lease->status = DHCPServerLease::STATUS_WAITING;
				if ($item['status'] == 'offered') $lease->status = DHCPServerLease::STATUS_OFFERED;
				if ($item['status'] == 'bound') $lease->status = DHCPServerLease::STATUS_BOUND;
			}
			
			if (isset($item['expires-after']))
				$lease->expiration = QuarkDate::NowUTC()->Offset(self::DateInterval($item['expires-after']), true);
			
			$out[] = $lease;
		}
		
		unset($i, $item, $lease);
		
		return $out;
	}
	
	/**
	 * @param string $date
	 *
	 * @return QuarkDateInterval
	 */
	public static function DateInterval ($date) {
		if (!preg_match('#((\d{1,2})w)?((\d{1,2})d)?((\d{1,2})h)?((\d{1,2})m)?((\d{1,2})s)?$#is', $date, $found)) return null;
		
		$out = new QuarkDateInterval(
			0,
			0,
			isset($found[4]) && $found[4] != '' ? (int)$found[4] : 0,
			isset($found[6]) && $found[6] != '' ? (int)$found[6] : 0,
			isset($found[8]) && $found[8] != '' ? (int)$found[8] : 0,
			isset($found[10]) && $found[10] != '' ? (int)$found[10] : 0
		);
		
		if (isset($found[2]) && $found[2] != '')
			$out->Offset('+' . $found[2] . QuarkDateInterval::UNIT_WEEK);
		
		return $out;
	}
	
	/**
	 * @param string $command
	 * @param string[] $args = []
	 *
	 * @return bool
	 */
	private function _send ($command, $args = []) {
		$msg = $this->_command($command, $args);

		if ($this->_debug) {
			Quark::Log('[Mikrotik::_send::debug] > ');
			Quark::Trace($msg);
		}

		return $this->_client->Send($msg);
	}

	/**
	 * @param string $command
	 * @param string[] $args = []
	 *
	 * @return string
	 */
	private function _command ($command, $args = []) {
		$out = array($command);
		$buffer = '';

		foreach ($args as $key => &$value) {
			$buffer = '=' . $key . '=' . $value;

			if (is_array($value)) {
				foreach ($value as $modifier => &$val) {
					if ($modifier == '?')
						$buffer = $key . '=' . $val;

					if ($modifier == '~')
						$buffer = $key . '~' . $val;
				}
			}

			$out[] = $buffer;
		}

		return $this->_serializeCommand($out);
	}

	/**
	 * @param string[] $command = []
	 *
	 * @return string
	 */
	private function _serializeCommand ($command = []) {
		$out = '';

		foreach ($command as $i => &$argument)
			$out .= $this->_serializeLength(strlen($argument)) . $argument;

		return $out . chr(0);
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	private function _serializeLength ($length) {
		if ($length < 0x80) {
			$length = chr($length);
		}
		elseif ($length < 0x4000) {
			$length |= 0x8000;
			$length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
		}
		elseif ($length < 0x200000) {
			$length |= 0xC00000;
			$length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
		}
		elseif ($length < 0x10000000) {
			$length |= 0xE0000000;
			$length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
		}
		elseif ($length >= 0x10000000) {
			$length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
		}

		return $length;
	}
	
	/**
	 * @return int
	 */
	private function _read () {
		$raw = $this->_client->ReceiveExact(1);
		
		return $raw === false ? 0 : ord($raw);
	}
	
	/**
	 * @return array
	 */
	private function _unserialize () {
		$response = array();
		$received = false;
		
		while (!$received) {
			// Read the first byte of input which gives us some or all of the length
			// of the remaining reply.
			$byte = $this->_read();
			$length = $byte;
			
			// If the first bit is set then we need to remove the first four bits, shift left 8
			// and then read another byte in.
			// We repeat this for the second and third bits.
			// If the fourth bit is set, we need to remove anything left in the first byte
			// and then read in yet another byte.
			if ($byte & 128) {
				if (($byte & 192) == 128) {
					$length = (($byte & 63) << 8) + $this->_read();
				}
				else {
					if (($byte & 224) == 192) {
						$length = (($byte & 31) << 8) + $this->_read();
						$length = ($length << 8) + $this->_read();
					}
					else {
						if (($byte & 240) == 224) {
							$length = (($byte & 15) << 8) + $this->_read();
							$length = ($length << 8) + $this->_read();
							$length = ($length << 8) + $this->_read();
						}
						else {
							$length = $this->_read();
							$length = ($length << 8) + $this->_read();
							$length = ($length << 8) + $this->_read();
							$length = ($length << 8) + $this->_read();
						}
					}
				}
			}
			
			$buffer = '';
			$bufferLen = 0;
			
			// If we have got more characters to read, read them in.
			if ($length > 0) {
				while ($bufferLen < $length) {
					$buffer .= $this->_client->ReceiveExact($length - $bufferLen);
					$bufferLen = strlen($buffer);
				}
				
				$response[] = $buffer;
			}
			
			$received = $buffer == self::RESULT_DONE;
		}
		
		//print_r($response);
		
		$out = array();
		
		if (is_array($response)) {
			$buff = null;
			$value = null;
			
			foreach ($response as $x) {
				if (in_array($x, self::$_resultError)) {
					if ($x == self::RESULT_RE) {
						$buff =& $out[];
					}
					else {
						$buff =& $out[$x][];
					}
				}
				else {
					if ($x == self::RESULT_DONE) $value = true;
					else {
						$found = array();
						
						if (preg_match_all('/[^=]+/i', $x, $found)) {
							if ($found[0][0] == 'ret') {
								$value = $found[0][1];
							}
							
							$buff[$found[0][0]] = (isset($found[0][1]) ? $found[0][1] : '');
						}
					}
				}
			}
			
			if (empty($out) && $value !== null) {
				$out = $value;
			}
		}
		
		return $out;
	}
}