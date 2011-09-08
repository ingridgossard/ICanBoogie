<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

if (!defined('ICanBoogie\DOCUMENT_ROOT'))
{
	/**
	 * @var string Document root of the application.
	 */
	define('ICanBoogie\DOCUMENT_ROOT', rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

if (!defined('ICanBoogie\ROOT'))
{
	/**
	 * @var string The ROOT directory of the ICanBoogie framework.
	 */
	define('ICanBoogie\ROOT', rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
}

/**
 * @var string Path to the ICanBoogie's assets directory.
 */
define('ICanBoogie\ASSETS', ROOT . 'assets' . DIRECTORY_SEPARATOR);


/**
 * @var bool If true, an APC cache is used to store and retrieve active records.
 */
define('ICanBoogie\CACHE_ACTIVERECORDS', false);

/**
 * @var string Version string of the ICanBoogie framework.
 */
define('ICanBoogie\VERSION', '0.11.0-dev (2011-08-01)');

/**
 * @var string The request path string correspond to the REQUEST_URI stripped of its QUERY_STRING.
 * It is added to the $_SERVER global array as 'REQUEST_PATH'.
 */
if ($_SERVER['QUERY_STRING'])
{
	$_SERVER['REQUEST_PATH'] = substr($_SERVER['REQUEST_URI'], 0, -strlen($_SERVER['QUERY_STRING']) - 1);
}
else
{
	$_SERVER['REQUEST_PATH'] = $_SERVER['REQUEST_URI'];
}

require_once ROOT . '/lib/toolkit/helpers.php';
require_once ROOT . '/lib/i18n/helpers.php';
require_once ROOT . '/lib/core/debug.php';
require_once ROOT . '/lib/core/object.php';
require_once ROOT . '/lib/core/accessor/configs.php';
require_once ROOT . '/lib/core/core.php';
require_once ROOT . '/lib/i18n/translator.php'; // TODO-20110716: this is required because of the `format` function used by Debug. We should externalize the function as an helper.

/* TODO-20110716: THE FOLLOWING FUNCTIONS SHOULD BE MOVED TO OTHER PLACES */

/**
 * Normalize a string to be suitable as a namespace part.
 *
 * @param string $part The string to normalize.
 *
 * @return string Normalized string.
 */
function normalize_namespace_part($part)
{
	return preg_replace_callback
	(
		'/[-\s_\.]\D/', function ($match)
		{
			$rc = ucfirst($match[0]{1});

			if ($match[0]{0} == '.')
			{
				$rc = '\\' . $rc;
			}

			return $rc;
		},

		' ' . $part
	);
}

// https://github.com/rails/rails/blob/master/activesupport/lib/active_support/inflector/inflections.rb
// http://api.rubyonrails.org/classes/ActiveSupport/Inflector.html#method-i-singularize

function singularize($string)
{
	static $rules = array
	(
		'/ies$/' => 'y',
		'/s$/' => ''
	);

	return preg_replace(array_keys($rules), $rules, $string);
}