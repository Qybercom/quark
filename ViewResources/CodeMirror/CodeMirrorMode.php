<?php
namespace Quark\ViewResources\CodeMirror;

use Quark\IQuarkForeignViewResource;
use Quark\IQuarkSpecifiedViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkDTO;
use Quark\QuarkJSViewResourceType;

/**
 * Class CodeMirrorMode
 *
 * @package Quark\ViewResources\CodeMirror
 */
class CodeMirrorMode implements IQuarkSpecifiedViewResource, IQuarkForeignViewResource {
	const NAME_API = 'apl/apl';
	const NAME_ASCIIARMOR = 'asciiarmor/asciiarmor';
	const NAME_ASN1 = 'asn.1/asn.1';
	const NAME_ASTERISK = 'asterisk/asterisk';
	const NAME_BRAINFUCK = 'brainfuck/brainfuck';
	const NAME_CLIKE = 'clike/clike';
	const NAME_CLOJURE = 'clojure/clojure';
	const NAME_CMAKE = 'cmake/cmake';
	const NAME_COBOL = 'cobol/cobol';
	const NAME_COFFESCRIPT = 'coffeescript/coffeescript';
	const NAME_COMMONLISP = 'commonlisp/commonlisp';
	const NAME_CRYSTAL = 'crystal/crystal';
	const NAME_CSS = 'css/css';
	const NAME_CYPHER = 'cypher/cypher';
	const NAME_D = 'd/d';
	const NAME_DART = 'dart/dart';
	const NAME_DIFF = 'diff/diff';
	const NAME_DJANGO = 'django/django';
	const NAME_DOCKERFILE = 'dockerfile/dockerfile';
	const NAME_DTD = 'dtd/dtd';
	const NAME_DYLAN = 'dylan/dylan';
	const NAME_EBNF = 'ebnf/ebnf';
	const NAME_ECL = 'ecl/ecl';
	const NAME_EIFFEL = 'eiffel/eiffel';
	const NAME_ELM = 'elm/elm';
	const NAME_ERLANG = 'erlang/erlang';
	const NAME_FACTOR = 'factor/factor';
	const NAME_FCL = 'fcl/fcl';
	const NAME_FORTH = 'forth/forth';
	const NAME_FORTRAN = 'fortran/fortran';
	const NAME_GAS = 'gas/gas';
	const NAME_GFM = 'gfm/gfm';
	const NAME_GHERKIN = 'gherkin/gherkin';
	const NAME_GO = 'go/go';
	const NAME_GROOVY = 'groovy/groovy';
	const NAME_HAML = 'haml/haml';
	const NAME_HANDLEBARS = 'handlebars/handlebars';
	const NAME_HASKELL_LITERATE = 'haskell-literate/haskell-literate';
	const NAME_HASKELL = 'haskell/haskell';
	const NAME_HAXE = 'haxe/haxe';
	const NAME_HTMLEMBEDDED = 'htmlembedded/htmlembedded';
	const NAME_HTMLMIXED = 'htmlmixed/htmlmixed';
	const NAME_HTTP = 'http/http';
	const NAME_IDL = 'idl/idl';
	const NAME_JAVASCRIPT = 'javascript/javascript';
	const NAME_JINJA = 'jinja2/jinja2';
	const NAME_JSX = 'jsx/jsx';
	const NAME_JULIA = 'julia/julia';
	const NAME_LIVESCRIPT = 'livescript/livescript';
	const NAME_LUA = 'lua/lua';
	const NAME_MARKDOWN = 'markdown/markdown';
	const NAME_MATHEMATICA = 'mathematica/mathematica';
	const NAME_MBOX = 'mbox/mbox';
	const NAME_META = 'meta';
	const NAME_MIRC = 'mirc/mirc';
	const NAME_MLLIKE = 'mllike/mllike';
	const NAME_MODELICA = 'modelica/modelica';
	const NAME_MSCGEN = 'mscgen/mscgen';
	const NAME_MUMPS = 'mumps/mumps';
	const NAME_NGINX = 'nginx/nginx';
	const NAME_NSIS = 'nsis/nsis';
	const NAME_NTRIPLES = 'ntriples/ntriples';
	const NAME_OCTAVE = 'octave/octave';
	const NAME_OZ = 'oz/oz';
	const NAME_PASCAL = 'pascal/pascal';
	const NAME_PEGJS = 'pegjs/pegjs';
	const NAME_PERL = 'perl/perl';
	const NAME_PHP = 'php/php';
	const NAME_PIG = 'pig/pig';
	const NAME_POWERSHELL = 'powershell/powershell';
	const NAME_PROPERTIES = 'properties/properties';
	const NAME_PROTOBUF = 'protobuf/protobuf';
	const NAME_PUG = 'pug/pug';
	const NAME_PUPPET = 'puppet/puppet';
	const NAME_PYTHON = 'python/python';
	const NAME_Q = 'q/q';
	const NAME_R = 'r/r';
	const NAME_RPM = 'rpm/rpm';
	const NAME_RST = 'rst/rst';
	const NAME_RUBY = 'ruby/ruby';
	const NAME_RUST = 'rust/rust';
	const NAME_SAS = 'sas/sas';
	const NAME_SASS = 'sass/sass';
	const NAME_SCHEME = 'scheme/scheme';
	const NAME_SHELL = 'shell/shell';
	const NAME_SIEVE = 'sieve/sieve';
	const NAME_SLIM = 'slim/slim';
	const NAME_SMALLTALK = 'smalltalk/smalltalk';
	const NAME_SMARTY = 'smarty/smarty';
	const NAME_SOLR = 'solr/solr';
	const NAME_SOY = 'soy/soy';
	const NAME_SPARQL = 'sparql/sparql';
	const NAME_SPREADSHEET = 'spreadsheet/spreadsheet';
	const NAME_SQL = 'sql/sql';
	const NAME_STEX = 'stex/stex';
	const NAME_STYLUS = 'stylus/stylus';
	const NAME_SWIFT = 'swift/swift';
	const NAME_TCL = 'tcl/tcl';
	const NAME_TEXTILE = 'textile/textile';
	const NAME_TIDDLWIKI = 'tiddlywiki/tiddlywiki';
	const NAME_TIKI = 'tiki/tiki';
	const NAME_TOML = 'toml/toml';
	const NAME_TORNADO = 'tornado/tornado';
	const NAME_TROFF = 'troff/troff';
	const NAME_TTCN_CFG = 'ttcn-cfg/ttcn-cfg';
	const NAME_TTCN = 'ttcn/ttcn';
	const NAME_TURTLE = 'turtle/turtle';
	const NAME_TWIG = 'twig/twig';
	const NAME_VB = 'vb/vb';
	const NAME_VBSCRIPT = 'vbscript/vbscript';
	const NAME_VELOCITY = 'velocity/velocity';
	const NAME_VERILOG = 'verilog/verilog';
	const NAME_VHDL = 'vhdl/vhdl';
	const NAME_VUE = 'vue/vue';
	const NAME_WAST = 'wast/wast';
	const NAME_WEBIDL = 'webidl/webidl';
	const NAME_XML = 'xml/xml';
	const NAME_XQUERY = 'xquery/xquery';
	const NAME_YACAS = 'yacas/yacas';
	const NAME_YAML_FRONTMATTER = 'yaml-frontmatter/yaml-frontmatter';
	const NAME_YAML = 'yaml/yaml';
	const NAME_Z80 = 'z80/z80';

	/**
	 * @var string $_name
	 */
	private $_name;

	/**
	 * @var string $_version
	 */
	private $_version = CodeMirror::VERSION_CURRENT;

	/**
	 * @param string $name
	 * @param string $version = CodeMirror::VERSION_CURRENT
	 */
	public function __construct ($name, $version = CodeMirror::VERSION_CURRENT) {
		$this->_name = $name;
		$this->_version = $version;
	}

	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/' . $this->_version . '/mode/' . $this->_name . '.min.js';
	}

	/**
	 * @return QuarkDTO
	 */
	public function RequestDTO () {
		// TODO: Implement RequestDTO() method.
	}


}