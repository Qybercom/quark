<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkCredentials;
use Quark\QuarkConnectionException;

/**
 * Class FileSystem
 *
 * @package Quark\DataProviders
 */
class FileSystem implements IQuarkDataProvider {
	const PROTOCOL = 'file://';
	const OPTIONS_UPSTREAM = 'upstream';
	const OPTIONS_SORT_BY_TYPE = 'sortByType';
	const OPTIONS_SORT_BY_NAME = 'sortByName';
	const OPTIONS_SORT_BY_EXTENSION = 'sortByExtension';

	private $_root = '';

	/**
	 * @param QuarkCredentials $credentials
	 *
	 * @return mixed
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkCredentials $credentials) {
		$this->_root = Quark::NormalizePath(Quark::SanitizePath(str_replace(self::PROTOCOL, '', preg_replace('#\/([a-zA-Z])\:#Uis', '$1:', $credentials->uri()))));
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		// TODO: Implement Create() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		// TODO: Implement Save() method.
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model) {
		// TODO: Implement Remove() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		$buffer = array();

		$raw = scandir($this->_root);

		if (!isset($options[self::OPTIONS_UPSTREAM]))
			$options[self::OPTIONS_UPSTREAM] = false;

		if ($options[self::OPTIONS_UPSTREAM] == false)
			foreach ($raw as $item) {
				if ($item == '.' || $item == '..') continue;

				$buffer[] = $item;
			}

		if (isset($options[self::OPTIONS_SORT_BY_NAME]))
			sort($buffer, $options[self::OPTIONS_SORT_BY_NAME]);

		if (isset($options[self::OPTIONS_SORT_BY_TYPE])) {
			$fs = array();

			foreach ($buffer as $entry)
				if (is_dir($this->_root . $entry)) $fs[] = $entry;

			foreach ($buffer as $entry)
				if (is_file($this->_root . $entry)) $fs[] = $entry;

			$buffer = $fs;
		}

		$output = array();

		\clearstatcache();

		foreach ($buffer as $file) {
			$target = $this->_root . $file;
			$isDir = is_dir($target);

			$output[] = array(
				'name' => $file,
				'isDir' => $isDir,
				'fullPath' => $target,
				'size' => $isDir ? '' : filesize($target)
			);
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 *
	 * @return IQuarkModel
	 */
	public function FindOne (IQuarkModel $model, $criteria) {
		// TODO: Implement FindOne() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 *
	 * @return IQuarkModel
	 */
	public function FindOneById (IQuarkModel $model, $id) {
		// TODO: Implement FindOneById() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Update (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement Update() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return mixed
	 */
	public function Delete (IQuarkModel $model, $criteria, $options) {
		// TODO: Implement Delete() method.
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $limit
	 * @param             $skip
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip) {
		// TODO: Implement Count() method.
	}
}