<?php
namespace Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO;

use Quark\QuarkDTO;
use Quark\QuarkXMLIOProcessor;
use Quark\QuarkXMLNode;

use Quark\Extensions\Quark\SOAP\SOAPElement;
use Quark\Extensions\Quark\SOAP\SOAPEnvelope;

use Quark\Extensions\UPnP\IQuarkUPnPProviderServiceControlDTO;
use Quark\Extensions\UPnP\Providers\DLNA\Elements\DLNAElementItem;
use Quark\Extensions\UPnP\Providers\DLNA\Services\DLNAServiceContentDirectory;

/**
 * Class DLNAServiceControlDTOXGetFeatureList
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA\ServiceControlDTO
 */
class DLNAServiceControlDTOXGetFeatureList implements IQuarkUPnPProviderServiceControlDTO {
	const XMLNS = 'urn:schemas-upnp-org:av:avs';
	const XMLNS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';
	const XSI_SCHEMA_LOCATION = ' urn:schemas-upnp-org:av:avs http://www.upnp.org/schemas/av/avs.xsd';

	const FEATURE_SAMSUNG_BASIC_VIEW_ID = 'samsung.com_BASICVIEW';
	const FEATURE_SAMSUNG_BASIC_VIEW_VERSION = '1';

	/**
	 * @var QuarkXMLNode[] $_features = []
	 */
	private $_features = array();

	/**
	 * @param string $name = ''
	 * @param string $version = '1'
	 * @param $content = []
	 *
	 * @return DLNAServiceControlDTOXGetFeatureList
	 */
	public function Feature ($name = '', $version = '1', $content = []) {
		if (func_num_args() != 0)
			$this->_features[] = new QuarkXMLNode('Feature', $content, array(
				'name' => $name,
				'version' => $version
			));

		return $this;
	}

	/**
	 * @return QuarkXMLNode[]
	 */
	public function Features () {
		return $this->_features;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkUPnPProviderServiceControlDTO
	 */
	public function UPnPProviderServiceControlRequest (QuarkDTO $request) {
		$soap = SOAPEnvelope::FromRequest($request);
		if ($soap == null) return null;

		$body = $soap->Body();
		if (sizeof($body) == 0) return null;

		$get = $body[0];
		if ($get->Name() != 'X_GetFeatureList') return null;

		return $this;
	}

	/**
	 * @return SOAPElement[]
	 */
	public function UPnPProviderServiceControlResponse () {
		$features = QuarkXMLNode::Root(
			'Features',
			array(
				'xmlns' => self::XMLNS,
				'xmlns:xsi' => self::XMLNS_XSI,
				'xsi:schemaLocation' => self::XSI_SCHEMA_LOCATION
			),
			$this->_features
		);

		$processor = new QuarkXMLIOProcessor();

		return new SOAPElement('u', 'X_GetFeatureListResponse', DLNAServiceContentDirectory::TYPE, array(
			new QuarkXMLNode('FeatureList', str_replace('<', '&lt;', str_replace('>', '&gt;', $processor->Encode($features))))
		));
	}

	/**
	 * @param string $rootImages = ''
	 * @param string $rootAudio = ''
	 * @param string $rootVideo = ''
	 *
	 * @return DLNAServiceControlDTOXGetFeatureList
	 */
	public static function WithSamsungBasicView ($rootImages = '', $rootAudio = '', $rootVideo = '') {
		$out = new self();
		$out->Feature(self::FEATURE_SAMSUNG_BASIC_VIEW_ID, self::FEATURE_SAMSUNG_BASIC_VIEW_VERSION, array(
			QuarkXMLNode::SingleNode('container', array(
				'id' => $rootImages,
				'type' => DLNAElementItem::UPnP_CLASS_IMAGE
			)),
			QuarkXMLNode::SingleNode('container', array(
				'id' => $rootAudio,
				'type' => DLNAElementItem::UPnP_CLASS_AUDIO
			)),
			QuarkXMLNode::SingleNode('container', array(
				'id' => $rootVideo,
				'type' => DLNAElementItem::UPnP_CLASS_VIDEO
			))
		));

		return $out;
	}
}