<?php
defined('BASEPATH') OR exit('No direct script access allowed');



if (is_php('5.6'))
{
	return;
}


if ( ! function_exists('hash_equals'))
{
		function hash_equals($known_string, $user_string)
	{
		if ( ! is_string($known_string))
		{
			trigger_error('hash_equals(): Expected known_string to be a string, '.strtolower(gettype($known_string)).' given', E_USER_WARNING);
			return FALSE;
		}
		elseif ( ! is_string($user_string))
		{
			trigger_error('hash_equals(): Expected user_string to be a string, '.strtolower(gettype($user_string)).' given', E_USER_WARNING);
			return FALSE;
		}
		elseif (($length = strlen($known_string)) !== strlen($user_string))
		{
			return FALSE;
		}

		$diff = 0;
		for ($i = 0; $i < $length; $i++)
		{
			$diff |= ord($known_string[$i]) ^ ord($user_string[$i]);
		}

		return ($diff === 0);
	}
}


if (is_php('5.5'))
{
	return;
}


if ( ! function_exists('hash_pbkdf2'))
{
		function hash_pbkdf2($algo, $password, $salt, $iterations, $length = 0, $raw_output = FALSE)
	{
		if ( ! in_array(strtolower($algo), hash_algos(), TRUE))
		{
			trigger_error('hash_pbkdf2(): Unknown hashing algorithm: '.$algo, E_USER_WARNING);
			return FALSE;
		}

		if (($type = gettype($iterations)) !== 'integer')
		{
			if ($type === 'object' && method_exists($iterations, '__toString'))
			{
				$iterations = (string) $iterations;
			}

			if (is_string($iterations) && is_numeric($iterations))
			{
				$iterations = (int) $iterations;
			}
			else
			{
				trigger_error('hash_pbkdf2() expects parameter 4 to be long, '.$type.' given', E_USER_WARNING);
				return NULL;
			}
		}

		if ($iterations < 1)
		{
			trigger_error('hash_pbkdf2(): Iterations must be a positive integer: '.$iterations, E_USER_WARNING);
			return FALSE;
		}

		if (($type = gettype($length)) !== 'integer')
		{
			if ($type === 'object' && method_exists($length, '__toString'))
			{
				$length = (string) $length;
			}

			if (is_string($length) && is_numeric($length))
			{
				$length = (int) $length;
			}
			else
			{
				trigger_error('hash_pbkdf2() expects parameter 5 to be long, '.$type.' given', E_USER_WARNING);
				return NULL;
			}
		}

		if ($length < 0)
		{
			trigger_error('hash_pbkdf2(): Length must be greater than or equal to 0: '.$length, E_USER_WARNING);
			return FALSE;
		}

		$hash_length = defined('MB_OVERLOAD_STRING')
			? mb_strlen(hash($algo, NULL, TRUE), '8bit')
			: strlen(hash($algo, NULL, TRUE));
		empty($length) && $length = $hash_length;

						static $block_sizes;
		empty($block_sizes) && $block_sizes = array(
			'gost' => 32,
			'haval128,3' => 128,
			'haval160,3' => 128,
			'haval192,3' => 128,
			'haval224,3' => 128,
			'haval256,3' => 128,
			'haval128,4' => 128,
			'haval160,4' => 128,
			'haval192,4' => 128,
			'haval224,4' => 128,
			'haval256,4' => 128,
			'haval128,5' => 128,
			'haval160,5' => 128,
			'haval192,5' => 128,
			'haval224,5' => 128,
			'haval256,5' => 128,
			'md2' => 16,
			'md4' => 64,
			'md5' => 64,
			'ripemd128' => 64,
			'ripemd160' => 64,
			'ripemd256' => 64,
			'ripemd320' => 64,
			'salsa10' => 64,
			'salsa20' => 64,
			'sha1' => 64,
			'sha224' => 64,
			'sha256' => 64,
			'sha384' => 128,
			'sha512' => 128,
			'snefru' => 32,
			'snefru256' => 32,
			'tiger128,3' => 64,
			'tiger160,3' => 64,
			'tiger192,3' => 64,
			'tiger128,4' => 64,
			'tiger160,4' => 64,
			'tiger192,4' => 64,
			'whirlpool' => 64
		);

		if (isset($block_sizes[$algo], $password[$block_sizes[$algo]]))
		{
			$password = hash($algo, $password, TRUE);
		}

		$hash = '';
				for ($bc = (int) ceil($length / $hash_length), $bi = 1; $bi <= $bc; $bi++)
		{
			$key = $derived_key = hash_hmac($algo, $salt.pack('N', $bi), $password, TRUE);
			for ($i = 1; $i < $iterations; $i++)
			{
				$derived_key ^= $key = hash_hmac($algo, $key, $password, TRUE);
			}

			$hash .= $derived_key;
		}

				if ( ! $raw_output)
		{
			$hash = bin2hex($hash);
		}

		return defined('MB_OVERLOAD_STRING')
			? mb_substr($hash, 0, $length, '8bit')
			: substr($hash, 0, $length);
	}
}
