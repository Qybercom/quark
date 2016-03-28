<?php
namespace Quark\Extensions\Quark\APIDoc;

use Quark\QuarkField;
use Quark\QuarkFile;
use Quark\QuarkArchException;

use Quark\ViewResources\ShowdownJS\ShowdownJS;

/**
 * Class QuarkAPIDoc
 *
 * @package Quark\Extensions\Quark\APIDoc
 */
class QuarkAPIDoc {
	const EXP_COMPONENT = '#\/\*\*\s\n \* Class (.*)\n(.*)\*\/.*class ([a-zA-Z0-9\_]*)( extends ([a-zA-Z0-9\_]*))? implements (([a-zA-Z0-9\_]*\,?\s?)*)\{(.*)}#is';
	const EXP_SERVICE_METHOD = '#\/\*\*(.*)\*\/.*function ([a-zA-Z0-9\_]*) ?\(.*\).*\{.*\}#Uis';
	const EXP_MODEL_FIELDS = '#function Fields ?\(.*\)(.*)\{.*\}#Uis';

	/**
	 * @param string $path
	 *
	 * @return string
	 * @throws QuarkArchException
	 */
	public static function OfArticle ($path = '') {
		$content = (new QuarkFile($path))->Load()->Content();

		return ShowdownJS::ToHTML($content);
	}

	/**
	 * @param string $path = ''
	 * @param bool $all = false
	 *
	 * @return QuarkAPIDocModel|QuarkAPIDocModel[]
	 * @throws QuarkArchException
	 */
	public static function OfModel ($path = '', $all = false) {
		$content = (new QuarkFile($path))->Load()->Content();
		$models = self::_component($content, self::EXP_COMPONENT);

		if (sizeof($models) == 0) return array();

		$out = array();

		foreach ($models as $model) {
			$fields = array();
			$properties = self::Attribute($model[1], 'property', false);

			foreach ($properties as $property) {
				$field = array_pad(explode(' ', $property), 3, '');
				$value = array_pad(explode('=', $property), 2, '');

				$fields[] = new QuarkField(str_replace('$', '', $field[1]), $field[0], trim($value[1]));
			}

			$out[] = new QuarkAPIDocModel(
				$model[3],
				self::Attribute($model[1], 'description'),
				$fields
			);
		}

		return $all ? $out : (isset($out[0]) ? $out[0] : null);
	}

	/**
	 * @param string $path = ''
	 * @param bool $all = false
	 *
	 * @return QuarkAPIDocService|QuarkAPIDocService[]
	 * @throws QuarkArchException
	 */
	public static function OfService ($path = '', $all = false) {
		$content = (new QuarkFile($path))->Load()->Content();
		$services = self::_component($content, self::EXP_COMPONENT);

		if (sizeof($services) == 0) return array();

		$out = array();

		foreach ($services as $service) {
			$methods = self::_component($service[8], self::EXP_SERVICE_METHOD);
			$actions = array();

			foreach ($methods as $method) {
				$uri = self::Attribute($method[1], 'request-uri');
				$actions[] = new QuarkAPIDocServiceMethod(
					trim($method[2]),
					self::Attribute($method[1], 'description'),
					new QuarkAPIDocServiceAuth(
						self::Attribute($method[1], 'auth-provider'),
						self::Attribute($method[1], 'auth-criteria'),
						self::Attribute($method[1], 'auth-failure')
					),
					new QuarkAPIDocServiceDataFlow(
						str_replace('<br />', '', self::Attribute($method[1], 'request')),
						$uri,
						self::Attribute($method[1], 'request-info')
					),
					new QuarkAPIDocServiceDataFlow(
						str_replace('<br />', '', self::Attribute($method[1], 'response')),
						$uri,
						self::Attribute($method[1], 'response-info')
					),
					new QuarkAPIDocServiceDataFlow(
						str_replace('<br />', '', self::Attribute($method[1], 'event')),
						$uri,
						self::Attribute($method[1], 'event-info')
					)
				);
			}

			$out[] = new QuarkAPIDocService(
				$service[3],
				self::Attribute($service[1], 'description'),
				self::Attribute($service[1], 'package'),
				$actions
			);
		}

		return $all ? $out : (isset($out[0]) ? $out[0] : null);
	}

	/**
	 * @param string $source
	 * @param string $name
	 * @param bool $single = true
	 *
	 * @return array|null
	 */
	public static function Attribute ($source, $name, $single = true) {
		$raw = self::_component($source, '#\@' . $name . ' (.*)' . ($single ? '(\@|\*\/)' : '') . '#' . (!$single ? '' : 'Uis'));

		if (sizeof($raw) == 0)
			return $single ? null : array();

		if ($single) return self::_attr($raw[0][1]);

		$out = array();

		foreach ($raw as $attr)
			$out[] = self::_attr($attr[1]);

		return $out;
	}

	/**
	 * @param string $source
	 * @param string $regex
	 *
	 * @return array
	 */
	private static function _component ($source, $regex) {
		if (preg_match_all($regex, $source, $found, PREG_SET_ORDER))
			return $found;

		return array();
	}

	/**
	 * @param string $attr
	 *
	 * @return string
	 */
	private static function _attr ($attr) {
		return trim(str_replace("\n", '<br />', trim(preg_replace('#[\t ]*\* ?(.*)#', '$1', trim($attr, "\t\r\n* ")))));
	}
}