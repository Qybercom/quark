<?php
namespace Quark\Extensions\UPnP;

use Quark\QuarkClient;
use Quark\QuarkDate;
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
	const EVENT_NOTIFY = 'UPnP.notify';
	const EVENT_SUBSCRIBE = 'UPnP.subscribe';
	const EVENT_M_SEARCH = 'UPnP.m-search';
	const EVENT_NOTIFY_OUT = 'UPnP.notify.out';

	const METHOD_NOTIFY = 'NOTIFY';
	const METHOD_SUBSCRIBE = 'SUBSCRIBE';
	const METHOD_M_SEARCH = 'M-SEARCH';

	const HEADER_NT = 'NT';
	const HEADER_NTS = 'NTS';
	const HEADER_USN = 'USN';

	const STATUS_ALIVE = 'alive';
	const STATUS_BYEBYE = 'byebye';

	const SERVICE_UPnP_ROOT_DEVICE = 'upnp:rootdevice';

	const DEFAULT_NOTIFYING_TIMEOUT = 10;
	const DEFAULT_NOTIFYING_MAX_AGE = 1800;

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
	 * @var int $_notifyingTimeout = self::DEFAULT_NOTIFYING_TIMEOUT (seconds)
	 */
	private $_notifyingTimeout = self::DEFAULT_NOTIFYING_TIMEOUT;

	/**
	 * @var int $_notifyingMaxAge = self::DEFAULT_NOTIFYING_MAX_AGE (seconds)
	 */
	private $_notifyingMaxAge = self::DEFAULT_NOTIFYING_MAX_AGE;

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
	 * @param int $timeout = self::DEFAULT_NOTIFYING_TIMEOUT (seconds)
	 *
	 * @return int
	 */
	public function NotifyingTimeout ($timeout = self::DEFAULT_NOTIFYING_TIMEOUT) {
		if (func_num_args() != 0)
			$this->_notifyingTimeout = $timeout;

		return $this->_notifyingTimeout;
	}

	/**
	 * @param int $maxAge = self::DEFAULT_NOTIFYING_MAX_AGE (seconds)
	 *
	 * @return int
	 */
	public function NotifyingMaxAge ($maxAge = self::DEFAULT_NOTIFYING_MAX_AGE) {
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

			if ($request->Method() == self::METHOD_M_SEARCH) {
				$this->TriggerArgs(self::EVENT_M_SEARCH, array(&$client, &$request));

				$services = $this->_rootDescription->Services();
				$address = $client->URI()->host . ':' . $client->URI()->port;

				$this->_notifyReply($this->ReplyServiceDTO(UPnPRootDescription::ROOT_DEVICE), $address);
				$this->_notifyReply($this->ReplyServiceDTO($this->_rootDescription->DeviceType()), $address);

				foreach ($services as $i => &$service)
					$this->_notifyReply($this->ReplyServiceDTO($service->ID()), $address);

				unset($i, $service, $address, $services);
			}

			if ($request->Method() == self::METHOD_NOTIFY)
				$this->TriggerArgs(self::EVENT_NOTIFY, array(&$client, &$request));

			if ($request->Method() == self::METHOD_SUBSCRIBE)
				$this->TriggerArgs(self::EVENT_SUBSCRIBE, array(&$client, &$request));

			unset($request, $data, $client);
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
				$this->TriggerArgs(self::EVENT_NOTIFY_OUT, array(&$this->_notifyingLast, &$now));
				$this->Notify();

				$this->_notifyingLast = clone $now;
			}

			unset($now);
		}

		return $this->_broadcast->Pipe();
	}

	/**
	 * @param QuarkDTO $dto
	 *
	 * @return QuarkDTO
	 */
	private function _commonHeaders (QuarkDTO $dto) {
		$dto->Header('CACHE-CONTROL', 'max-age = ' . $this->_notifyingMaxAge);
		$dto->Header('LOCATION', $this->_rootDescription->Location());
		$dto->Header('SERVER', $this->_rootDescription->ServerName());

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

		$request->Header('HOST', self::UPnP_HOST . ':' . self::UPnP_PORT);

		$request = $this->_commonHeaders($request);

		$request->Header('NTS', 'ssdp:' . $status);
		$request->Header('NT', $nt);
		$request->Header('USN', $usn);

		$request->HeaderControl(function ($headers) {
			foreach ($headers as $i => &$header) {
				$item = explode(':', $header);

				if ($item[0] == QuarkDTO::HEADER_CONTENT_TYPE) unset($headers[$i]);
				if ($item[0] == QuarkDTO::HEADER_CONTENT_LENGTH) unset($headers[$i]);
				if ($item[0] == QuarkDTO::HEADER_HOST) unset($headers[$i]);
			}

			unset($item, $i, $header);

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
		$ok = 0;
		$services = $this->_rootDescription->Services();

		$ok += $this->_notify($this->NotifyDeviceDTO());

		$ok += $this->_notify($this->NotifyServiceDTO(UPnPRootDescription::ROOT_DEVICE));
		$ok += $this->_notify($this->NotifyServiceDTO($this->_rootDescription->DeviceType()));

		foreach ($services as $s => &$service)
			$ok += $this->_notify($this->NotifyServiceDTO($service->Type()));

		unset($s, $service, $services);

		return $ok;
	}

	/**
	 * @param QuarkDTO $notice = null
	 *
	 * @return bool|int
	 */
	private function _notify (QuarkDTO $notice = null) {
		return $notice == null ? false : $this->_unicast->SendTo($notice->SerializeRequest());
	}

	/**
	 * @param QuarkDTO $reply = null
	 * @param string $address = ''
	 *
	 * @return bool|int
	 */
	private function _notifyReply (QuarkDTO $reply = null, $address = '') {
		return $reply == null ? false : $this->_unicast->SendTo($reply->SerializeResponse(), 0, $address);
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

			unset($item, $i, $header);

			return $headers;
		});

		return $response;
	}
}