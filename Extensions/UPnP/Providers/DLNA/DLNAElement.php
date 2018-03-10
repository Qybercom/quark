<?php
namespace Quark\Extensions\UPnP\Providers\DLNA;

use Quark\IQuarkModel;
use Quark\IQuarkStrongModel;

use Quark\QuarkCollection;
use Quark\QuarkFile;
use Quark\QuarkModel;
use Quark\QuarkModelBehavior;
use Quark\QuarkXMLNode;

use Quark\Extensions\UPnP\Providers\DLNA\ElementResources\DLNAElementResourceImage;

use Quark\Extensions\MediaProcessing\GraphicsDraw\GDImage;

/**
 * Class DLNAElement
 *
 * @property bool $container = false
 * @property string $id = ''
 * @property string $parentID = ''
 * @property int $childCount = 0
 * @property bool $restricted = false
 * @property bool $searchable = true
 * @property QuarkCollection|DLNAElementProperty[] $properties
 *
 * @package Quark\Extensions\UPnP\Providers\DLNA
 */
class DLNAElement implements IQuarkModel, IQuarkStrongModel {
	const ELEMENT_CONTAINER = 'container';
	const ELEMENT_CONTAINER_ROOT = '0';
	const ELEMENT_ITEM = 'item';
	const ELEMENT_ITEM_RESOURCE = 'res';

	const PROPERTY_DC_TITLE = 'dc:title';
	const PROPERTY_DC_DATE = 'dc:date';
	const PROPERTY_UPnP_CLASS = 'upnp:class';
	const PROPERTY_UPnP_ICON = 'upnp:icon';
	const PROPERTY_UPnP_GENRE = 'upnp:genre';
	const PROPERTY_UPnP_RATING = 'upnp:rating';
	const PROPERTY_UPnP_ALBUM_ART_URI = 'upnp:albumArtURI';
	const PROPERTY_RESOURCE = 'res';

	const UPnP_CLASS_CONTAINER_GENERIC = 'object.container';
	const UPnP_CLASS_CONTAINER_STORAGE = 'object.container.storageFolder';
	const UPnP_CLASS_ITEM_GENERIC = 'object.item';
	const UPnP_CLASS_ITEM_IMAGE = 'object.item.imageItem';
	const UPnP_CLASS_ITEM_IMAGE_PHOTO = 'object.item.imageItem.photo';
	const UPnP_CLASS_ITEM_AUDIO = 'object.item.audioItem';
	const UPnP_CLASS_ITEM_AUDIO_MUSIC_TRACK = 'object.item.audioItem.musicTrack';
	const UPnP_CLASS_ITEM_VIDEO = 'object.item.videoItem';

	const ATTRIBUTE_DLNA_PROFILE_ID = 'dlna:profileID';

	const DLNA_PROFILE_JPEG_TN = 'JPEG_TN'; // 160x160
	const DLNA_PROFILE_JPEG_SM = 'JPEG_SM'; // 640x480
	const DLNA_PROFILE_JPEG_MED = 'JPEG_MED'; // 1024x768
	const DLNA_PROFILE_JPEG_LRG = 'JPEG_LRG'; // 1920x1080

	use QuarkModelBehavior;

	/**
	 * @return mixed
	 */
	public function Fields () {
		return array(
			'container' => false,
			'id' => '',
			'parentID' => '',
			'childCount' => 0,
			'restricted' => false,
			'searchable' => true,
			'properties' => new QuarkCollection(new DLNAElementProperty())
		);
	}

	/**
	 * @return mixed
	 */
	public function Rules () {
		// TODO: Implement Rules() method.
	}

	/**
	 * @param string $title = ''
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function Title ($title = '') {
		if (func_num_args() == 0) {
			/**
			 * @var QuarkModel|DLNAElementProperty $property
			 */
			$property = $this->properties->SelectOne(array('name' => self::PROPERTY_DC_TITLE));

			return $property == null ? null : $property->value;
		}

		return $this->SingleProperty(self::PROPERTY_DC_TITLE, $title);
	}

	/**
	 * @param string $icon = ''
	 * @param string $type = ''
	 * @param int $size = 0
	 * @param int $width = 0
	 * @param int $height = 0
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function Icon ($icon = '', $type = '', $size = 0, $width = 0, $height = 0) {
		if (func_num_args() == 0) {
			/**
			 * @var QuarkModel|DLNAElementProperty $property
			 */
			$property = $this->properties->SelectOne(array('name' => self::PROPERTY_UPnP_ICON));

			return $property == null ? null : $property->value;
		}

		return $this
			->SingleProperty(self::PROPERTY_UPnP_ICON, $icon)
			->SingleProperty(self::PROPERTY_UPnP_ALBUM_ART_URI, $icon, array(
				self::ATTRIBUTE_DLNA_PROFILE_ID => self::DLNA_PROFILE_JPEG_TN
			))
			->Resource(new DLNAElementResourceImage($icon, $type, $size, $width, $height));
	}

	/**
	 * @param QuarkFile $icon = null
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function IconFromFile (QuarkFile $icon = null) {
		if ($icon == null)
			return $this->Container();

		$image = GDImage::FromFile($icon);

		return $this->Icon($icon->WebLocation(), $image->File()->type, $image->File()->size, $image->Width(), $image->Height());
	}

	/**
	 * @param string $name = ''
	 * @param string $value = ''
	 * @param array|object $attributes = []
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function Property ($name = '', $value = '', $attributes = []) {
		$this->properties[] = new QuarkModel(new DLNAElementProperty(), array(
			'name' => $name,
			'value' => $value,
			'attributes' => $attributes
		));

		return $this->Container();
	}

	/**
	 * @param string $name = ''
	 * @param string $value = ''
	 * @param array|object $attributes = []
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function SingleProperty ($name = '', $value = '', $attributes = []) {
		$this->properties->Purge(array(
			'name' => $name
		));

		return $this->Property($name, $value, $attributes);
	}

	/**
	 * @param IQuarkDLNAElementResource $resource = null
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public function Resource (IQuarkDLNAElementResource $resource = null) {
		if ($resource != null) {
			$pairs = $resource->DLNAElementResourceAttributes();
			$attributes = array();

			foreach ($pairs as $i => &$pair)
				$attributes[$pair->Key()] = $pair->Value();

			$this->Property(
				self::PROPERTY_RESOURCE,
				$resource->DLNAElementResourceURL(),
				$attributes
			);
		}

		return $this->Container();
	}

	/**
	 * @param string $id = ''
	 * @param string $parentID = self::ELEMENT_CONTAINER_ROOT
	 * @param int $childCount = 0
	 * @param string $UPnPClass = self::UPnP_CLASS_CONTAINER_STORAGE
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public static function ItemContainer ($id = '', $parentID = self::ELEMENT_CONTAINER_ROOT, $childCount = 0, $UPnPClass = self::UPnP_CLASS_CONTAINER_STORAGE) {
		/**
		 * @var QuarkModel|DLNAElement $out
		 */
		$out = new QuarkModel(new DLNAElement());

		$out->id = $id;
		$out->parentID = $parentID;
		$out->container = true;
		$out->childCount = $childCount;

		$out->Property(self::PROPERTY_UPnP_CLASS, $UPnPClass);

		return $out;
	}

	/**
	 * @param string $id = ''
	 * @param string $parentID = self::ELEMENT_CONTAINER_ROOT
	 * @param string $UPnPClass = self::UPnP_CLASS_ITEM_GENERIC
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public static function Item ($id = '', $parentID = self::ELEMENT_CONTAINER_ROOT, $UPnPClass = self::UPnP_CLASS_ITEM_GENERIC) {
		/**
		 * @var QuarkModel|DLNAElement $out
		 */
		$out = new QuarkModel(new DLNAElement());

		$out->id = $id;
		$out->parentID = $parentID;

		$out->Property(self::PROPERTY_UPnP_CLASS, $UPnPClass);

		return $out;
	}

	/**
	 * @param IQuarkDLNAElementResource $resource = null
	 * @param string $id = ''
	 * @param string $parentID = ''
	 *
	 * @return QuarkModel|DLNAElement
	 */
	public static function FromResource (IQuarkDLNAElementResource $resource = null, $id = '', $parentID = '') {
		if ($resource == null) return null;

		/**
		 * @var QuarkModel|DLNAElement $out
		 */
		$out = new QuarkModel(new DLNAElement());

		$out->properties = $resource->DLNAElementResourceItemProperties();
		$out->id = $id;
		$out->parentID = $parentID;

		return $out;
	}

	/**
	 * @return QuarkXMLNode
	 */
	public function ToXML () {
		$properties = array();

		foreach ($this->properties as $property)
			$properties[] = $property->XMLNode();

		if ($this->container) {
			return new QuarkXMLNode(self::ELEMENT_CONTAINER, $properties, array(
				'id' => $this->id,
				'parentID' => $this->parentID,
				'childCount' => $this->childCount,
				'restricted' => $this->restricted ? 'true' : 'false',
				'searchable' => $this->searchable ? 'true' : 'false'
			));
		}
		else {
			return new QuarkXMLNode(self::ELEMENT_ITEM, $properties, array(
				'id' => $this->id,
				'parentID' => $this->parentID,
				'restricted' => $this->restricted ? 'true' : 'false'
			));
		}
	}
}