<?php
namespace Quark\DataProviders;

use Quark\IQuarkDataProvider;
use Quark\IQuarkModel;

use Quark\Quark;
use Quark\QuarkFile;
use Quark\QuarkKeyValuePair;
use Quark\QuarkObject;
use Quark\QuarkURI;
use Quark\QuarkConnectionException;
use Quark\QuarkModel;

/**
 * Class FileSystem
 *
 * @package Quark\DataProviders
 */
class FileSystem implements IQuarkDataProvider {
	const PROTOCOL = 'file://';

	const FIRST_LOCATION = '_location';
	const LOCATION = 'location';
	const NAME = 'name';
	const EXTENSION = 'extension';
	const IS_DIR = 'isDir';
	const SIZE = 'size';
	const PARENT = 'parent';

	const OPTIONS_RECURSIVE = 'opt.recursive';
	const OPTIONS_JUMP = 'opt.jump';
	const OPTIONS_GROUP = 'group';

	private $_root = '';

	/**
	 * @return QuarkURI
	 */
	public static function LocalFS () {
		return QuarkURI::FromURI(Quark::Host(), false);
	}

	/**
	 * @param QuarkURI $uri
	 *
	 * @return mixed
	 *
	 * @throws QuarkConnectionException
	 */
	public function Connect (QuarkURI $uri) {
		$this->_root = Quark::NormalizePath($uri->path);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Create (IQuarkModel $model) {
		$location = QuarkObject::Property($model, self::LOCATION);

		return is_file($location) ? true : file_put_contents($location, '');
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Save (IQuarkModel $model) {
		$_location = QuarkObject::Property($model, self::FIRST_LOCATION);
		$location = QuarkObject::Property($model, self::LOCATION);

		return rename($_location, $location);
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return mixed
	 */
	public function Remove (IQuarkModel $model) {
		$location = QuarkObject::Property($model, self::LOCATION);

		return is_file($location) ? unlink($location) : false;
	}

	/**
	 * @param IQuarkModel $model
	 *
	 * @return string
	 */
	public function PrimaryKey (IQuarkModel $model) {
		return new QuarkKeyValuePair(self::LOCATION, '');
	}

	/**
	 * @param array $file
	 * @param      $condition
	 * @param bool $strict
	 * @param string $part
	 *
	 * @return bool
	 */
	private static function _condition ($file, $condition, $strict = true, $part = '') {
		if (!is_array($condition) || sizeof($condition) == 0) return true;

		$output = true;

		foreach ($condition as $key => $rule) {
			if (!is_scalar($key)) continue;

			$value = $rule;
			$part = func_num_args() == 4 ? $part : $key;

			if (is_array($rule))
				$value = self::_condition($file, $rule, true, $key);

			else switch ($key) {
				case '$and': $value = self::_condition($file, $rule); break;
				case '$or': $value = self::_condition($file, $rule, false); break;
				case '$lte': $value = self::_step($file, $part, '<=', $value); break;
				case '$lt': $value = self::_step($file, $part, '<', $value); break;
				case '$gt': $value = self::_step($file, $part, '>', $value); break;
				case '$gte': $value = self::_step($file, $part, '>=', $value); break;
				case '$ne': $value = self::_step($file, $part, '!=', $value); break;
				default: $value = self::_step($file, $part, '==', $value); break;
			}

			$output = self::_rule($strict, $output, $value);
		}

		return $output;
	}

	/**
	 * @param $file
	 * @param $key
	 * @param $rule
	 * @param $value
	 *
	 * @return bool
	 */
	private static function _step ($file, $key, $rule, $value) {
		return isset($file[$key]) ? eval('return $file[$key] ' . $rule . ' $value;') : false;
	}

	/**
	 * @param bool $strict
	 * @param bool $result
	 * @param bool $value
	 *
	 * @return bool
	 */
	private static function _rule ($strict, $result, $value) {
		return $strict ? $result && $value : $result || $value;
	}

	/**
	 * @param $location
	 * @param $name
	 * @param $extension
	 * @param $isDir
	 *
	 * @return array
	 */
	private static function _file ($location, $name, $extension, $isDir) {
		$location = Quark::NormalizePath($location, false);

		return array(
			self::FIRST_LOCATION => $location,
			self::LOCATION => $location,
			self::NAME => $name,
			self::EXTENSION => $extension,
			self::IS_DIR => $isDir,
			self::SIZE => $isDir ? '' : filesize($location),
			self::PARENT => str_replace($name, '', $location)
		);
	}

	/**
	 * @param mixed $criteria
	 * @param mixed $options
	 *
	 * @return array
	 */
	private function _find ($criteria = [], $options = []) {
		$output = array();

		if (!isset($options[self::OPTIONS_RECURSIVE]))
			$options[self::OPTIONS_RECURSIVE] = false;

		if (!isset($options[self::OPTIONS_JUMP]))
			$options[self::OPTIONS_JUMP] = false;

		if ($options[self::OPTIONS_RECURSIVE]) {
			$dir = new \RecursiveDirectoryIterator($this->_root);
			$fs = new \RecursiveIteratorIterator($dir);
		}
		else {
			$dir = new \DirectoryIterator($this->_root);
			$fs = new \IteratorIterator($dir);
		}

		foreach ($fs as $file) {
			/**
			 * @var \FilesystemIterator $file
			 */

			$name = $file->getFilename();

			if ($options[self::OPTIONS_JUMP] == false && ($name == '.' || $name == '..')) continue;

			$buffer = self::_file($file->getRealPath(), $name, $file->getExtension(), $file->isDir());

			if (self::_condition($buffer, $criteria))
				$output[] = $buffer;
		}

		return $output;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param array $options
	 *
	 * @return array
	 */
	public function Find (IQuarkModel $model, $criteria, $options = []) {
		$buffer = self::_find($criteria, $options);

		if (isset($options[QuarkModel::OPTION_SORT]) && QuarkObject::isAssociative($options[QuarkModel::OPTION_SORT])) {
			$sort = $options[QuarkModel::OPTION_SORT];

			foreach ($sort as $key => $rule) {
				usort($buffer, function ($a, $b) use ($key) {
					if (!isset($a[$key]) || !isset($b[$key])) return 0;

					if (is_bool($a[$key]) || is_bool($b[$key])) return self::_cmp($a[$key], $b[$key]);
					if (is_string($a[$key]) || is_string($b[$key])) return strnatcmp($a[$key], $b[$key]);

					return 0;
				});

				if ($rule == -1)
					$buffer = array_reverse($buffer);
			}
		}

		return $buffer;
	}

	/**
	 * @param bool $a
	 * @param bool $b
	 *
	 * @return int
	 */
	private static function _cmp ($a, $b) {
		if ($a && $b) return 0;
		elseif ($a && !$b) return 1;
		elseif (!$a && $b) return -1;
		else return 0;
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $criteria
	 * @param             $options
	 *
	 * @return IQuarkModel
	 */
	public function FindOne (IQuarkModel $model, $criteria, $options) {
		$buffer = self::_find($criteria);

		return sizeof($buffer) == 0 ? null : $buffer[0];
	}

	/**
	 * @param IQuarkModel $model
	 * @param             $id
	 * @param             $options
	 *
	 * @return IQuarkModel
	 */
	public function FindOneById (IQuarkModel $model, $id, $options) {
		$file = new QuarkFile($id);
		return self::_file($file->location, $file->name, $file->extension, $file->isDir);
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
	 * @param             $options
	 *
	 * @return int
	 */
	public function Count (IQuarkModel $model, $criteria, $limit, $skip, $options) {
		return sizeof(self::_find($criteria));
	}
}