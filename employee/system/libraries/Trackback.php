<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CI_Trackback {

		public $charset = 'UTF-8';

		public $data = array(
		'url' => '',
		'title' => '',
		'excerpt' => '',
		'blog_name' => '',
		'charset' => ''
	);

		public $convert_ascii = TRUE;

		public $response = '';

		public $error_msg = array();

	
		public function __construct()
	{
		log_message('info', 'Trackback Class Initialized');
	}

	
		public function send($tb_data)
	{
		if ( ! is_array($tb_data))
		{
			$this->set_error('The send() method must be passed an array');
			return FALSE;
		}

				foreach (array('url', 'title', 'excerpt', 'blog_name', 'ping_url') as $item)
		{
			if ( ! isset($tb_data[$item]))
			{
				$this->set_error('Required item missing: '.$item);
				return FALSE;
			}

			switch ($item)
			{
				case 'ping_url':
					$$item = $this->extract_urls($tb_data[$item]);
					break;
				case 'excerpt':
					$$item = $this->limit_characters($this->convert_xml(strip_tags(stripslashes($tb_data[$item]))));
					break;
				case 'url':
					$$item = str_replace('&#45;', '-', $this->convert_xml(strip_tags(stripslashes($tb_data[$item]))));
					break;
				default:
					$$item = $this->convert_xml(strip_tags(stripslashes($tb_data[$item])));
					break;
			}

						if ($this->convert_ascii === TRUE && in_array($item, array('excerpt', 'title', 'blog_name'), TRUE))
			{
				$$item = $this->convert_ascii($$item);
			}
		}

				$charset = isset($tb_data['charset']) ? $tb_data['charset'] : $this->charset;

		$data = 'url='.rawurlencode($url).'&title='.rawurlencode($title).'&blog_name='.rawurlencode($blog_name)
			.'&excerpt='.rawurlencode($excerpt).'&charset='.rawurlencode($charset);

				$return = TRUE;
		if (count($ping_url) > 0)
		{
			foreach ($ping_url as $url)
			{
				if ($this->process($url, $data) === FALSE)
				{
					$return = FALSE;
				}
			}
		}

		return $return;
	}

	
		public function receive()
	{
		foreach (array('url', 'title', 'blog_name', 'excerpt') as $val)
		{
			if (empty($_POST[$val]))
			{
				$this->set_error('The following required POST variable is missing: '.$val);
				return FALSE;
			}

			$this->data['charset'] = isset($_POST['charset']) ? strtoupper(trim($_POST['charset'])) : 'auto';

			if ($val !== 'url' && MB_ENABLED === TRUE)
			{
				if (MB_ENABLED === TRUE)
				{
					$_POST[$val] = mb_convert_encoding($_POST[$val], $this->charset, $this->data['charset']);
				}
				elseif (ICONV_ENABLED === TRUE)
				{
					$_POST[$val] = @iconv($this->data['charset'], $this->charset.'				}
			}

			$_POST[$val] = ($val !== 'url') ? $this->convert_xml(strip_tags($_POST[$val])) : strip_tags($_POST[$val]);

			if ($val === 'excerpt')
			{
				$_POST['excerpt'] = $this->limit_characters($_POST['excerpt']);
			}

			$this->data[$val] = $_POST[$val];
		}

		return TRUE;
	}

	
		public function send_error($message = 'Incomplete Information')
	{
		exit('<?xml version="1.0" encoding="utf-8"?'.">\n<response>\n<error>1</error>\n<message>".$message."</message>\n</response>");
	}

	
		public function send_success()
	{
		exit('<?xml version="1.0" encoding="utf-8"?'.">\n<response>\n<error>0</error>\n</response>");
	}

	
		public function data($item)
	{
		return isset($this->data[$item]) ? $this->data[$item] : '';
	}

	
		public function process($url, $data)
	{
		$target = parse_url($url);

				if ( ! $fp = @fsockopen($target['host'], 80))
		{
			$this->set_error('Invalid Connection: '.$url);
			return FALSE;
		}

				$path = isset($target['path']) ? $target['path'] : $url;
		empty($target['query']) OR $path .= '?'.$target['query'];

				if ($id = $this->get_id($url))
		{
			$data = 'tb_id='.$id.'&'.$data;
		}

				fputs($fp, 'POST '.$path." HTTP/1.0\r\n");
		fputs($fp, 'Host: '.$target['host']."\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, 'Content-length: '.strlen($data)."\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);

		
		$this->response = '';
		while ( ! feof($fp))
		{
			$this->response .= fgets($fp, 128);
		}
		@fclose($fp);

		if (stripos($this->response, '<error>0</error>') === FALSE)
		{
			$message = preg_match('/<message>(.*?)<\/message>/is', $this->response, $match)
				? trim($match[1])
				: 'An unknown error was encountered';
			$this->set_error($message);
			return FALSE;
		}

		return TRUE;
	}

	
		public function extract_urls($urls)
	{
				$urls = str_replace(',,', ',', preg_replace('/\s*(\S+)\s*/', '\\1,', $urls));

				$urls = array_unique(preg_split('/[,]/', rtrim($urls, ',')));

		array_walk($urls, array($this, 'validate_url'));
		return $urls;
	}

	
		public function validate_url(&$url)
	{
		$url = trim($url);

		if (stripos($url, 'http') !== 0)
		{
			$url = 'http:		}
	}

	
		public function get_id($url)
	{
		$tb_id = '';

		if (strpos($url, '?') !== FALSE)
		{
			$tb_array = explode('/', $url);
			$tb_end   = $tb_array[count($tb_array)-1];

			if ( ! is_numeric($tb_end))
			{
				$tb_end  = $tb_array[count($tb_array)-2];
			}

			$tb_array = explode('=', $tb_end);
			$tb_id	= $tb_array[count($tb_array)-1];
		}
		else
		{
			$url = rtrim($url, '/');

			$tb_array = explode('/', $url);
			$tb_id	= $tb_array[count($tb_array)-1];

			if ( ! is_numeric($tb_id))
			{
				$tb_id = $tb_array[count($tb_array)-2];
			}
		}

		return ctype_digit((string) $tb_id) ? $tb_id : FALSE;
	}

	
		public function convert_xml($str)
	{
		$temp = '__TEMP_AMPERSANDS__';

		$str = preg_replace(array('/&#(\d+);/', '/&(\w+);/'), $temp.'\\1;', $str);

		$str = str_replace(array('&', '<', '>', '"', "'", '-'),
					array('&amp;', '&lt;', '&gt;', '&quot;', '&#39;', '&#45;'),
					$str);

		return preg_replace(array('/'.$temp.'(\d+);/', '/'.$temp.'(\w+);/'), array('&#\\1;', '&\\1;'), $str);
	}

	
		public function limit_characters($str, $n = 500, $end_char = '&#8230;')
	{
		if (strlen($str) < $n)
		{
			return $str;
		}

		$str = preg_replace('/\s+/', ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

		if (strlen($str) <= $n)
		{
			return $str;
		}

		$out = '';
		foreach (explode(' ', trim($str)) as $val)
		{
			$out .= $val.' ';
			if (strlen($out) >= $n)
			{
				return rtrim($out).$end_char;
			}
		}
	}

	
		public function convert_ascii($str)
	{
		$count	= 1;
		$out	= '';
		$temp	= array();

		for ($i = 0, $s = strlen($str); $i < $s; $i++)
		{
			$ordinal = ord($str[$i]);

			if ($ordinal < 128)
			{
				$out .= $str[$i];
			}
			else
			{
				if (count($temp) === 0)
				{
					$count = ($ordinal < 224) ? 2 : 3;
				}

				$temp[] = $ordinal;

				if (count($temp) === $count)
				{
					$number = ($count === 3)
						? (($temp[0] % 16) * 4096) + (($temp[1] % 64) * 64) + ($temp[2] % 64)
						: (($temp[0] % 32) * 64) + ($temp[1] % 64);

					$out .= '&#'.$number.';';
					$count = 1;
					$temp = array();
				}
			}
		}

		return $out;
	}

	
		public function set_error($msg)
	{
		log_message('error', $msg);
		$this->error_msg[] = $msg;
	}

	
		public function display_errors($open = '<p>', $close = '</p>')
	{
		return (count($this->error_msg) > 0) ? $open.implode($close.$open, $this->error_msg).$close : '';
	}

}
