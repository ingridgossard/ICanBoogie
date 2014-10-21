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

/**
 * Patchable helpers of the ICanBoogie package.
 *
 * @method string generate_token() generate_token($length=8, $possible=TOKEN_WIDE)
 * @method string pbkdf2() pbkdf2($p, $s, $c=1000, $kl=32, $a='sha256')
 */
class Helpers
{
	static private $jumptable = [

		'generate_token' => [ __CLASS__, 'generate_token' ],
		'pbkdf2' => [ __CLASS__, 'pbkdf2' ]

	];

	/**
	 * Calls the callback of a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param array $arguments Arguments.
	 *
	 * @return mixed
	 */
	static public function __callstatic($name, array $arguments)
	{
		return call_user_func_array(self::$jumptable[$name], $arguments);
	}

	/**
	 * Patches a patchable function.
	 *
	 * @param string $name Name of the function.
	 * @param collable $callback Callback.
	 *
	 * @throws \RuntimeException is attempt to patch an undefined function.
	 */
	static public function patch($name, $callback)
	{
		if (empty(self::$jumptable[$name]))
		{
			throw new \RuntimeException("Undefined patchable: $name.");
		}

		self::$jumptable[$name] = $callback;
	}

	/*
	 * Default implementations
	 */

	static private function generate_token($length=8, $possible=TOKEN_NARROW)
	{
		$token = '';
		$y = strlen($possible) - 1;

		while ($length--)
		{
			$i = mt_rand(0, $y);
			$token .= $possible[$i];
		}

		return $token;
	}

	static private function pbkdf2($p, $s, $c=1000, $kl=32, $a='sha256')
	{
		$hl = strlen(hash($a, null, true)); # Hash length
		$kb = ceil($kl / $hl); # Key blocks to compute
		$dk = ''; # Derived key

		# Create key
		for ($block = 1 ; $block <= $kb ; $block++)
		{
			# Initial hash for this block
			$ib = $b = hash_hmac($a, $s . pack('N', $block), $p, true);
			# Perform block iterations
			for ( $i = 1; $i < $c; $i ++ )
			# XOR each iterate
			$ib ^= ($b = hash_hmac($a, $b, $p, true));
			$dk .= $ib; # Append iterated block
		}

		# Return derived key of correct length
		return substr($dk, 0, $kl);
	}
}

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

/**
 * Creates an excerpt of an HTML string.
 *
 * The following tags are preserved: A, P, CODE, DEL, EM, INS and STRONG.
 *
 * @param string $str HTML string.
 * @param int $limit The maximum number of words.
 *
 * @return string
 */
function excerpt($str, $limit=55)
{
	static $allowed_tags = [

		'a', 'p', 'code', 'del', 'em', 'ins', 'strong'

	];

	$str = strip_tags(trim($str), '<' . implode('><', $allowed_tags) . '>');
	$str = preg_replace('#(<p>|<p\s+[^\>]+>)\s*</p>#', '', $str);

	$parts = preg_split('#<([^\s>]+)([^>]*)>#m', $str, 0, PREG_SPLIT_DELIM_CAPTURE);

	# i+0: text
	# i+1: markup ('/' prefix for closing markups)
	# i+2: markup attributes

	$rc = '';
	$opened = [];

	foreach ($parts as $i => $part)
	{
		if ($i % 3 == 0)
		{
			$words = preg_split('#(\s+)#', $part, 0, PREG_SPLIT_DELIM_CAPTURE);

			foreach ($words as $w => $word)
			{
				if ($w % 2 == 0)
				{
					if (!$word) // TODO-20100908: strip punctuation
					{
						continue;
					}

					$rc .= $word;

					if (!--$limit)
					{
						break;
					}
				}
				else
				{
					$rc .= $word;
				}
			}

			if (!$limit)
			{
				break;
			}
		}
		else if ($i % 3 == 1)
		{
			if ($part[0] == '/')
			{
				$rc .= '<' . $part . '>';

				array_shift($opened);
			}
			else
			{
				array_unshift($opened, $part);

				$rc .= '<' . $part . $parts[$i + 1] . '>';
			}
		}
	}

	if (!$limit)
	{
		$rc .= ' <span class="excerpt-warp">[…]</span>';
	}

	if ($opened)
	{
		$rc .= '</' . implode('></', $opened) . '>';
	}

	return $rc;
}

/**
 * Removes the `DOCUMENT_ROOT` from the provided path.
 *
 * Note: Because this function is usually used to create URL path from server path, the directory
 * separator '\' is replaced by '/'.
 *
 * @param string $pathname
 *
 * @return string
 */
function strip_root($pathname)
{
	$root = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
	$root = strtr($root, DIRECTORY_SEPARATOR == '/' ? '\\' : '/', DIRECTORY_SEPARATOR);
	$pathname = strtr($pathname, DIRECTORY_SEPARATOR == '/' ? '\\' : '/', DIRECTORY_SEPARATOR);

	if ($root && strpos($pathname, $root) === 0)
	{
		$pathname = substr($pathname, strlen($root));
	}

	if (DIRECTORY_SEPARATOR != '/')
	{
		$pathname = strtr($pathname, DIRECTORY_SEPARATOR, '/');
	}

	return $pathname;
}