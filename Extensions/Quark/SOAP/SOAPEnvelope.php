<?php
namespace Quark\Extensions\Quark\SOAP;

use Quark\QuarkDTO;
use Quark\QuarkObject;
use Quark\QuarkXMLNode;

/**
 * Class SOAPEnvelope
 *
 * https://www.w3.org/TR/2007/REC-soap12-part0-20070427/
 * https://ru.wikipedia.org/wiki/SOAP
 * https://ru.stackoverflow.com/questions/257184/%D0%A7%D1%82%D0%BE-%D1%82%D0%B0%D0%BA%D0%BE%D0%B5-soap
 *
 * @package QuarkTools\SOAP
 */
class SOAPEnvelope {
	const KEY = 'soap';

	const SCHEMA_ENVELOPE = 'http://schemas.xmlsoap.org/soap/envelope/';
	const SCHEMA_ENCODING = 'http://schemas.xmlsoap.org/soap/encoding/';

	/**
	 * @var string $_key = self::KEY
	 */
	private $_key = self::KEY;

	/**
	 * @var SOAPElement[] $_headers = []
	 */
	private $_headers = array();

	/**
	 * @var SOAPElement[] $_body = []
	 */
	private $_body = array();

	/**
	 * @param string $key = self::KEY
	 */
	public function __construct ($key = self::KEY) {
		$this->Key($key);
	}

	/**
	 * @param string $key = self::KEY
	 *
	 * @return string
	 */
	public function Key ($key = self::KEY) {
		if (func_num_args() != 0)
			$this->_key = $key;

		return $this->_key;
	}

	/**
	 * @param SOAPElement[] $headers = []
	 *
	 * @return SOAPElement[]
	 */
	public function Headers ($headers = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($headers, new SOAPElement()))
			$this->_headers = $headers;

		return $this->_headers;
	}

	/**
	 * @param SOAPElement $header = null
	 *
	 * @return SOAPEnvelope
	 */
	public function Header (SOAPElement $header = null) {
		if ($header != null)
			$this->_headers[] = $header;

		return $this;
	}

	/**
	 * @param SOAPElement[] $body = []
	 *
	 * @return SOAPElement[]
	 */
	public function Body ($body = []) {
		if (func_num_args() != 0 && QuarkObject::IsArrayOf($body, new SOAPElement()))
			$this->_body = $body;

		return $this->_body;
	}

	/**
	 * @param SOAPElement $item = null
	 *
	 * @return SOAPEnvelope
	 */
	public function BodyItem (SOAPElement $item = null) {
		if ($item != null)
			$this->_body[] = $item;

		return $this;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function Response () {
		$response = array();

		if (sizeof($this->_headers) != 0) {
			$response[$this->_key . ':Header'] = array();

			foreach ($this->_headers as $header)
				$response[$this->_key . ':Header'][] = $header->ToXML();
		}

		if (sizeof($this->_body) != 0) {
			$response[$this->_key . ':Body'] = array();

			foreach ($this->_body as $item)
				$response[$this->_key . ':Body'] [] = $item->ToXML();
		}

		return QuarkXMLNode::Root(
			$this->_key . ':Envelope',
			array(
				'xmlns:' . $this->_key => self::SCHEMA_ENVELOPE,
				$this->_key . ':encodingStyle' => self::SCHEMA_ENCODING
			),
			$response
		);
	}

	/**
	 * @param QuarkDTO $request = null
	 * @param string $key = self::KEY
	 *
	 * @return SOAPEnvelope
	 */
	public static function FromRequest (QuarkDTO $request = null, $key = self::KEY) {
		if ($request == null) return null;

		$data = $request->Data();
		$found = $key;

		if (QuarkObject::isTraversable($data)) {
			/** @noinspection PhpUnusedLocalVariableInspection */

			foreach ($data as $k => &$v) {
				$kName = explode(':', $k);

				if (sizeof($kName) == 2 && $kName[1] == 'Envelope') {
					$found = $kName[0];
					break;
				}
			}
		}

		$root = $found . ':Envelope';

		/**
		 * @var QuarkXMLNode $envelope
		 */
		$envelope = $request->$root;

		if (!$envelope || !($envelope instanceof QuarkXMLNode)) return null;

		$out = new self($key);

		$headers = $envelope->Get($found . ':Header');
		if (QuarkObject::isTraversable($headers))
			foreach ($headers as $i => &$header)
				if ($header instanceof QuarkXMLNode)
					$out->Header(SOAPElement::FromXML($header));

		$body = $envelope->Get($found . ':Body');
		if (QuarkObject::isTraversable($body))
			foreach ($body as $i => &$item)
				if ($item instanceof QuarkXMLNode)
					$out->BodyItem(SOAPElement::FromXML($item));

		return $out;
	}
}