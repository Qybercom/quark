<?php
namespace Quark\Scenarios;

use Quark\IQuarkAsyncTask;
use Quark\IQuarkTask;

use Quark\Quark;
use Quark\QuarkField;
use Quark\QuarkFile;
use Quark\QuarkObject;
use Quark\QuarkSQL;

/**
 * Class ModelForSchema
 *
 * @package Quark\Scenarios
 */
class ModelForSchema implements IQuarkTask, IQuarkAsyncTask {
	const TODO_FIELDS = '// TODO: Implement Fields() method.';
	const TODO_DATA_PROVIDER = '// TODO: Implement DataProvider() method.';

	public static function Contents ($name, $fields = [], $connection = '') {
		$import = '';

		if (sizeof($fields) == 0) $fieldsOut = self::TODO_FIELDS;
		else {
			$fieldsOut = "return array(\r\n";

			foreach ($fields as $field) {
				if (!($field instanceof QuarkField)) continue;

				if ($field->Type() == QuarkField::TYPE_DATE)
					$import .= "use Quark\\QuarkDate;\r\n";

				$fieldsOut .= "\t\t\t'" . $field->Name() . "' => " . $field->StringifyValue() . ",\r\n";
			}

			$fieldsOut .= "\t\t);";
		}

		$connOut = strlen($connection) != 0
			? ('return ' . $connection . ';')
			: self::TODO_DATA_PROVIDER;

		return "<?php\r\n" .
				"namespace Models;\r\n" .
				"\r\n" .
				"use Quark\\IQuarkModel;\r\n" .
				"use Quark\\IQuarkModelWithDataProvider;\r\n" .
				"use Quark\\IQuarkStrongModel;\r\n" .
				(strlen($import) != 0
					? ("\r\n" . $import)
					: ''
				) .
				"\r\n" .
				"/**\r\n" .
				" * Class " . $name . "\r\n" .
				" *\r\n" .
				" * @package Models\r\n" .
				" */\r\n" .
				"class " . $name . " implements IQuarkModel, IQuarkStrongModel, IQuarkModelWithDataProvider {\r\n" .
				"\t/**\r\n" .
				"\t * @return string\r\n" .
				"\t */\r\n" .
				"\tpublic function DataProvider () {\r\n" .
				"\t\t" . $connOut . "\r\n" .
				"\t}\r\n" .
				"\t\r\n" .
				"\t/**\r\n" .
				"\t * @return mixed\r\n" .
				"\t */\r\n" .
				"\tpublic function Fields () {\r\n" .
				"\t\t" . $fieldsOut . "\r\n" .
				"\t}\r\n" .
				"\t\r\n" .
				"\t/**\r\n" .
				"\t * @return mixed\r\n" .
				"\t */\r\n" .
				"\tpublic function Rules () {\r\n" .
				"\t\t// TODO: Implement Rules() method.\r\n" .
				"\t}\r\n" .
				'}';
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function OnLaunch ($argc, $argv) {
		// TODO: Implement OnLaunch() method.
	}

	/**
	 * @param int $argc
	 * @param array $argv
	 *
	 * @return mixed
	 */
	public function Task ($argc, $argv) {
		echo 'Generating model for table ', $argv[4], '... ', (self::Generate($argv[3], $argv[4]) ? 'OK' : 'FAIL');
	}

	/**
	 * @param string $connection
	 * @param string $table
	 * @param string|bool $file = true
	 *
	 * @return string|bool
	 */
	public static function Generate ($connection, $table, $file = true) {
		$schema = QuarkSQL::Schema($connection, $table);
		if (!$schema) return false;

		$conn = QuarkObject::ConstByValue($connection);
		$contents = self::Contents($table, $schema, $conn ? $conn : '\'' . $connection . '\'');

		if ($file == false) return $contents;
		if ($file === true)
			$file = Quark::Host() . '/Models/' . $table . '.php';

		$model = new QuarkFile($file);

		if ($model->Exists()) {
			$model->Load();

			if ($model->size != 0)
				$contents = $model->Content() . str_replace("<?php\r\nnamespace Models;", "\r\n\r\n\r\n", $contents);
		}

		$model->Content($contents);

		return $model->SaveContent();
	}
}