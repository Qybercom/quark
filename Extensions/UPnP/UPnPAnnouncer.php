<?php
namespace Quark\Extensions\UPnP;

use Quark\Quark;
use Quark\QuarkClient;
use Quark\QuarkDate;
use Quark\QuarkDateInterval;
use Quark\QuarkDTO;
use Quark\QuarkEvent;
use Quark\QuarkServer;
use Quark\QuarkURI;

/**
 * Class UPnPAnnouncer
 *
 * https://www.iana.org/assignments/multicast-addresses/multicast-addresses.xhtml
 * http://upnp.org/resources/documents/UPnP_UDA_tutorial_July2014.pdf
 * http://www.upnp.org/specs/av/UPnP-av-ContentDirectory-v1-Service.pdf
 *
 * https://dangfan.me/en/posts/upnp-intro
 * http://upnp.org/specs/av/UPnP-av-MediaServer-v1-Device.pdf
 *
 * https://github.com/ttyridal/phpdlna
 * https://github.com/reactphp/datagram/blob/master/src/Socket.php
 *
 * @package Quark\Extensions\UPnP
 */
class UPnPAnnouncer {
	const UPnP_HOST = '239.255.255.250';
	const UPnP_PORT = 1900;

	const EVENT_DATA = 'UPnP.data';
	const EVENT_ERROR = 'UPnP.error';
	const EVENT_NOTIFYING = 'UPnP.notifying';
	const EVENT_M_SEARCH = 'UPnP.m-search';

	const METHOD_NOTIFY = 'NOTIFY';
	const METHOD_SUBSCRIBE = 'SUBSCRIBE';
	const METHOD_M_SEARCH = 'M-SEARCH';

	const HEADER_NT = 'NT';
	const HEADER_NTS = 'NTS';
	const HEADER_USN = 'USN';

	const STATUS_ALIVE = 'alive';
	const STATUS_BYEBYE = 'byebye';

	const SERVICE_UPnP_ROOT_DEVICE = 'upnp:rootdevice';

	use QuarkEvent;

	/**
	 * @var QuarkServer $_broadcast
	 */
	private $_broadcast;

	/**
	 * @var QuarkClient $_unicast
	 */
	private $_unicast;

	/**
	 * @var UPnPRootDescription $_rootDescription
	 */
	private $_rootDescription;

	/**
	 * @var int $_notifyingTimeout = 10 (seconds)
	 */
	private $_notifyingTimeout = 10;

	/**
	 * @var int $_notifyingMaxAge = QuarkDateInterval::SECONDS_IN_HOUR (seconds)
	 */
	private $_notifyingMaxAge = QuarkDateInterval::SECONDS_IN_HOUR;

	/**
	 * @var QuarkDate $_notifyingLast
	 */
	private $_notifyingLast;

	/**
	 * @param UPnPRootDescription $rootDescription
	 * @param string $host = QuarkURI::HOST_LOCALHOST
	 */
	public function __construct (UPnPRootDescription $rootDescription, $host = QuarkURI::HOST_LOCALHOST) {
		$this->RootDescription($rootDescription);

		$this->_broadcast = new QuarkServer('udp://' . $host			. ':' . self::UPnP_PORT);
		$this->_unicast =   new QuarkClient('udp://' . self::UPnP_HOST	. ':' . self::UPnP_PORT);
	}

	/**
	 * @param UPnPRootDescription $rootDescription = null
	 *
	 * @return UPnPRootDescription
	 */
	public function &RootDescription (UPnPRootDescription $rootDescription = null) {
		if ($rootDescription != null)
			$this->_rootDescription = $rootDescription;

		return $this->_rootDescription;
	}

	/**
	 * @param string $host = QuarkURI::HOST_LOCALHOST
	 *
	 * @return string
	 */
	public function Host ($host = QuarkURI::HOST_LOCALHOST) {
		if (func_num_args() != 0)
			$this->_broadcast->URI()->host = $host;

		return $this->_broadcast->URI()->host;
	}

	/**
	 * @param int $timeout = 10 (seconds)
	 *
	 * @return int
	 */
	public function NotifyingTimeout ($timeout = 10) {
		if (func_num_args() != 0)
			$this->_notifyingTimeout = $timeout;

		return $this->_notifyingTimeout;
	}

	/**
	 * @param int $maxAge = QuarkDateInterval::SECONDS_IN_HOUR (seconds)
	 *
	 * @return int
	 */
	public function NotifyingMaxAge ($maxAge = QuarkDateInterval::SECONDS_IN_HOUR) {
		if (func_num_args() != 0)
			$this->_notifyingMaxAge = $maxAge;

		return $this->_notifyingMaxAge;
	}

	/**
	 * @return bool
	 */
	public function Bind () {
		$this->_broadcast->On(QuarkClient::EVENT_DATA, function (QuarkClient $client, $data) {
			$this->TriggerArgs(self::EVENT_DATA, array(&$client, $data));

			$request = new QuarkDTO();
			$request->UnserializeRequest($data);

			if ($request->Method() == UPnPAnnouncer::METHOD_M_SEARCH) {
				$this->TriggerArgs(self::EVENT_M_SEARCH, array(&$client, &$request));

				$services = $this->_rootDescription->Services();

				foreach ($services as $service)
					$client->SendTo($this->ReplyServiceDTO($service->ID())->SerializeResponse());
			}
		});

		$this->_broadcast->On(QuarkClient::EVENT_ERROR_PROTOCOL, function (QuarkClient $client, $error) {
			$this->TriggerArgs(self::EVENT_ERROR, array(&$client, $error));
		});

		return $this->_broadcast->Bind()
			&& $this->_broadcast->MultiCastGroupJoin(self::UPnP_HOST)
			&& $this->_unicast->Connect();
	}

	/**
	 * @return bool
	 */
	public function Pipe () {
		if ($this->_notifyingTimeout >= 0) {
			$now = QuarkDate::GMTNow();

			if ($this->_notifyingLast == null || $this->_notifyingLast->Earlier($now->Offset('-' . $this->_notifyingTimeout . ' seconds', true))) {
				$this->TriggerArgs(self::EVENT_NOTIFYING, array(&$this->_notifyingLast, &$now));
				$this->Notify();

				$this->_notifyingLast = $now;
			}
		}

		return $this->_broadcast->Pipe();
	}

	/**
	 * @param string $name = ''
	 *
	 * @return string
	 */
	public static function NameOf ($name = '') {
		return php_uname('s') . ', UPnP/1.0, ' . $name;
	}

	/**
	 * @param QuarkDTO $dto
	 *
	 * @return QuarkDTO
	 */
	private function _commonHeaders (QuarkDTO $dto) {
		$dto->Header('CACHE-CONTROL', 'max-age=' . $this->_notifyingMaxAge);
		$dto->Header('LOCATION', $this->_rootDescription->Location());
		$dto->Header('SERVER', self::NameOf($this->_rootDescription->ModelName()));

		return $dto;
	}

	/**
	 * @param string $nt = ''
	 * @param string $usn = ''
	 * @param string $status = ''
	 *
	 * @return QuarkDTO
	 */
	public function NotifyDTO ($nt = '', $usn = '', $status = '') {
		$request = QuarkDTO::ForRequest(self::METHOD_NOTIFY);

		$request->URI(QuarkURI::FromURI('*'));
		$request->Protocol(QuarkDTO::HTTP_VERSION_1_1);

		$request = $this->_commonHeaders($request);

		$request->Header('HOST', self::UPnP_HOST . ':' . self::UPnP_PORT);
		$request->Header('NT', $nt);
		$request->Header('USN', $usn);
		$request->Header('NTS', 'ssdp:' . $status);

		$request->HeaderControl(function ($headers) {
			foreach ($headers as $i => &$header) {
				$item = explode(':', $header);

				if ($item[0] == QuarkDTO::HEADER_CONTENT_TYPE) unset($headers[$i]);
				if ($item[0] == QuarkDTO::HEADER_CONTENT_LENGTH) unset($headers[$i]);
				if ($item[0] == QuarkDTO::HEADER_HOST) unset($headers[$i]);
			}

			return $headers;
		});

		return $request;
	}

	/**
	 * @param string $status = self::STATUS_ALIVE
	 *
	 * @return QuarkDTO
	 */
	public function NotifyDeviceDTO ($status = self::STATUS_ALIVE) {
		return $this->NotifyDTO('uuid:' . $this->_rootDescription->UuID(), 'uuid:' . $this->_rootDescription->UuID(), $status);
	}

	/**
	 * @param string $service = ''
	 * @param string $status = self::STATUS_ALIVE
	 *
	 * @return QuarkDTO
	 */
	public function NotifyServiceDTO ($service = '', $status = self::STATUS_ALIVE) {
		return $this->NotifyDTO($service, 'uuid:' . $this->_rootDescription->UuID() . '::' . $service, $status);
	}

	/**
	 * @return int
	 */
	public function Notify () {
		$services = $this->_rootDescription->Services();

		$ok = $this->_unicast->SendTo($n1 = $this->NotifyDeviceDTO()->SerializeRequest());

		$ok += $this->_unicast->SendTo($n2 = $this->NotifyServiceDTO(UPnPRootDescription::ROOT_DEVICE)->SerializeRequest());
		$ok += $this->_unicast->SendTo($n3 = $this->NotifyServiceDTO($this->_rootDescription->DeviceType())->SerializeRequest());

		foreach ($services as $i => &$service)
			$ok += $this->_unicast->SendTo($n4 = $this->NotifyServiceDTO($service->Type())->SerializeRequest());

		return $ok;
	}

	/**
	 * @param string $service = ''
	 *
	 * @return QuarkDTO
	 */
	public function ReplyServiceDTO ($service = '') {
		$response = new QuarkDTO();

		$response->Protocol(QuarkDTO::HTTP_VERSION_1_1);

		$response->Header('EXT', '');

		$response = $this->_commonHeaders($response);

		$response->Header('ST', $service);
		$response->Header('USN', 'uuid:' . $this->_rootDescription->UuID() . '::' . $service);

		$response->HeaderControl(function ($headers) {
			foreach ($headers as $i => &$header) {
				$item = explode(':', $header);

				if ($item[0] == QuarkDTO::HEADER_CONTENT_TYPE) unset($headers[$i]);
				if ($item[0] == QuarkDTO::HEADER_CONTENT_LENGTH) unset($headers[$i]);
			}

			return $headers;
		});

		return $response;
	}
}