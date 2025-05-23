<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('xml_parser_create'))
{
	show_error('Your PHP installation does not support XML');
}

if ( ! class_exists('CI_Xmlrpc', FALSE))
{
	show_error('You must load the Xmlrpc class before loading the Xmlrpcs class in order to create a server.');
}


class CI_Xmlrpcs extends CI_Xmlrpc {

		public $methods = array();

		public $debug_msg = '';

		public $system_methods	= array();

		public $object = FALSE;

		public function __construct($config = array())
	{
		parent::__construct();
		$this->set_system_methods();

		if (isset($config['functions']) && is_array($config['functions']))
		{
			$this->methods = array_merge($this->methods, $config['functions']);
		}

		log_message('info', 'XML-RPC Server Class Initialized');
	}

	
		public function initialize($config = array())
	{
		if (isset($config['functions']) && is_array($config['functions']))
		{
			$this->methods = array_merge($this->methods, $config['functions']);
		}

		if (isset($config['debug']))
		{
			$this->debug = $config['debug'];
		}

		if (isset($config['object']) && is_object($config['object']))
		{
			$this->object = $config['object'];
		}

		if (isset($config['xss_clean']))
		{
			$this->xss_clean = $config['xss_clean'];
		}
	}

	
		public function set_system_methods()
	{
		$this->methods = array(
					'system.listMethods'	 => array(
										'function' => 'this.listMethods',
										'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString), array($this->xmlrpcArray)),
										'docstring' => 'Returns an array of available methods on this server'),
					'system.methodHelp'	 => array(
										'function' => 'this.methodHelp',
										'signature' => array(array($this->xmlrpcString, $this->xmlrpcString)),
										'docstring' => 'Returns a documentation string for the specified method'),
					'system.methodSignature' => array(
										'function' => 'this.methodSignature',
										'signature' => array(array($this->xmlrpcArray, $this->xmlrpcString)),
										'docstring' => 'Returns an array describing the return type and required parameters of a method'),
					'system.multicall'	 => array(
										'function' => 'this.multicall',
										'signature' => array(array($this->xmlrpcArray, $this->xmlrpcArray)),
										'docstring' => 'Combine multiple RPC calls in one request. See http:				);
	}

	
		public function serve()
	{
		$r = $this->parseRequest();
		$payload = '<?xml version="1.0" encoding="'.$this->xmlrpc_defencoding.'"?'.'>'."\n".$this->debug_msg.$r->prepare_response();

		header('Content-Type: text/xml');
		header('Content-Length: '.strlen($payload));
		exit($payload);
	}

	
		public function add_to_map($methodname, $function, $sig, $doc)
	{
		$this->methods[$methodname] = array(
			'function'	=> $function,
			'signature'	=> $sig,
			'docstring'	=> $doc
		);
	}

	
		public function parseRequest($data = '')
	{
						
		if ($data === '')
		{
			$CI =& get_instance();
			if ($CI->input->method() === 'post')
			{
				$data = $CI->input->raw_input_stream;
			}
		}

						
		$parser = xml_parser_create($this->xmlrpc_defencoding);
		$parser_object = new XML_RPC_Message('filler');
		$pname = (string) $parser;

		$parser_object->xh[$pname] = array(
			'isf' => 0,
			'isf_reason' => '',
			'params' => array(),
			'stack' => array(),
			'valuestack' => array(),
			'method' => ''
		);

		xml_set_object($parser, $parser_object);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, TRUE);
		xml_set_element_handler($parser, 'open_tag', 'closing_tag');
		xml_set_character_data_handler($parser, 'character_data');
		
						
		if ( ! xml_parse($parser, $data, 1))
		{
						$r = new XML_RPC_Response(0,
				$this->xmlrpcerrxml + xml_get_error_code($parser),
				sprintf('XML error: %s at line %d',
				xml_error_string(xml_get_error_code($parser)),
				xml_get_current_line_number($parser)));
			xml_parser_free($parser);
		}
		elseif ($parser_object->xh[$pname]['isf'])
		{
			return new XML_RPC_Response(0, $this->xmlrpcerr['invalid_return'], $this->xmlrpcstr['invalid_return']);
		}
		else
		{
			xml_parser_free($parser);

			$m = new XML_RPC_Message($parser_object->xh[$pname]['method']);
			$plist = '';

			for ($i = 0, $c = count($parser_object->xh[$pname]['params']); $i < $c; $i++)
			{
				if ($this->debug === TRUE)
				{
					$plist .= $i.' - '.print_r(get_object_vars($parser_object->xh[$pname]['params'][$i]), TRUE).";\n";
				}

				$m->addParam($parser_object->xh[$pname]['params'][$i]);
			}

			if ($this->debug === TRUE)
			{
				echo "<pre>---PLIST---\n".$plist."\n---PLIST END---\n\n</pre>";
			}

			$r = $this->_execute($m);
		}

						
		if ($this->debug === TRUE)
		{
			$this->debug_msg = "<!-- DEBUG INFO:\n\n".$plist."\n END DEBUG-->\n";
		}

		return $r;
	}

	
		protected function _execute($m)
	{
		$methName = $m->method_name;

				$system_call = (strpos($methName, 'system') === 0);

		if ($this->xss_clean === FALSE)
		{
			$m->xss_clean = FALSE;
		}

						
		if ( ! isset($this->methods[$methName]['function']))
		{
			return new XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
		}

						
		$method_parts = explode('.', $this->methods[$methName]['function']);
		$objectCall   = ! empty($method_parts[1]);

		if ($system_call === TRUE)
		{
			if ( ! is_callable(array($this, $method_parts[1])))
			{
				return new XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
			}
		}
		elseif (($objectCall && ( ! method_exists($method_parts[0], $method_parts[1]) OR ! (new ReflectionMethod($method_parts[0], $method_parts[1]))->isPublic()))
			OR ( ! $objectCall && ! is_callable($this->methods[$methName]['function']))
		)
		{
			return new XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);
		}

						
		if (isset($this->methods[$methName]['signature']))
		{
			$sig = $this->methods[$methName]['signature'];
			for ($i = 0, $c = count($sig); $i < $c; $i++)
			{
				$current_sig = $sig[$i];

				if (count($current_sig) === count($m->params)+1)
				{
					for ($n = 0, $mc = count($m->params); $n < $mc; $n++)
					{
						$p = $m->params[$n];
						$pt = ($p->kindOf() === 'scalar') ? $p->scalarval() : $p->kindOf();

						if ($pt !== $current_sig[$n+1])
						{
							$pno = $n+1;
							$wanted = $current_sig[$n+1];

							return new XML_RPC_Response(0,
								$this->xmlrpcerr['incorrect_params'],
								$this->xmlrpcstr['incorrect_params'] .
								': Wanted '.$wanted.', got '.$pt.' at param '.$pno.')');
						}
					}
				}
			}
		}

						
		if ($objectCall === TRUE)
		{
			if ($method_parts[0] === 'this' && $system_call === TRUE)
			{
				return call_user_func(array($this, $method_parts[1]), $m);
			}
			elseif ($this->object === FALSE)
			{
				return get_instance()->{$method_parts[1]}($m);
			}

			return $this->object->{$method_parts[1]}($m);
		}

		return call_user_func($this->methods[$methName]['function'], $m);
	}

	
		public function listMethods($m)
	{
		$v = new XML_RPC_Values();
		$output = array();

		foreach ($this->methods as $key => $value)
		{
			$output[] = new XML_RPC_Values($key, 'string');
		}

		foreach ($this->system_methods as $key => $value)
		{
			$output[] = new XML_RPC_Values($key, 'string');
		}

		$v->addArray($output);
		return new XML_RPC_Response($v);
	}

	
		public function methodSignature($m)
	{
		$parameters = $m->output_parameters();
		$method_name = $parameters[0];

		if (isset($this->methods[$method_name]))
		{
			if ($this->methods[$method_name]['signature'])
			{
				$sigs = array();
				$signature = $this->methods[$method_name]['signature'];

				for ($i = 0, $c = count($signature); $i < $c; $i++)
				{
					$cursig = array();
					$inSig = $signature[$i];
					for ($j = 0, $jc = count($inSig); $j < $jc; $j++)
					{
						$cursig[]= new XML_RPC_Values($inSig[$j], 'string');
					}
					$sigs[] = new XML_RPC_Values($cursig, 'array');
				}

				return new XML_RPC_Response(new XML_RPC_Values($sigs, 'array'));
			}

			return new XML_RPC_Response(new XML_RPC_Values('undef', 'string'));
		}

		return new XML_RPC_Response(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
	}

	
		public function methodHelp($m)
	{
		$parameters = $m->output_parameters();
		$method_name = $parameters[0];

		if (isset($this->methods[$method_name]))
		{
			$docstring = isset($this->methods[$method_name]['docstring']) ? $this->methods[$method_name]['docstring'] : '';

			return new XML_RPC_Response(new XML_RPC_Values($docstring, 'string'));
		}

		return new XML_RPC_Response(0, $this->xmlrpcerr['introspect_unknown'], $this->xmlrpcstr['introspect_unknown']);
	}

	
		public function multicall($m)
	{
				return new XML_RPC_Response(0, $this->xmlrpcerr['unknown_method'], $this->xmlrpcstr['unknown_method']);

		$parameters = $m->output_parameters();
		$calls = $parameters[0];

		$result = array();

		foreach ($calls as $value)
		{
			$m = new XML_RPC_Message($value[0]);
			$plist = '';

			for ($i = 0, $c = count($value[1]); $i < $c; $i++)
			{
				$m->addParam(new XML_RPC_Values($value[1][$i], 'string'));
			}

			$attempt = $this->_execute($m);

			if ($attempt->faultCode() !== 0)
			{
				return $attempt;
			}

			$result[] = new XML_RPC_Values(array($attempt->value()), 'array');
		}

		return new XML_RPC_Response(new XML_RPC_Values($result, 'array'));
	}

	
		public function multicall_error($err)
	{
		$str = is_string($err) ? $this->xmlrpcstr["multicall_${err}"] : $err->faultString();
		$code = is_string($err) ? $this->xmlrpcerr["multicall_${err}"] : $err->faultCode();

		$struct['faultCode'] = new XML_RPC_Values($code, 'int');
		$struct['faultString'] = new XML_RPC_Values($str, 'string');

		return new XML_RPC_Values($struct, 'struct');
	}

	
		public function do_multicall($call)
	{
		if ($call->kindOf() !== 'struct')
		{
			return $this->multicall_error('notstruct');
		}
		elseif ( ! $methName = $call->me['struct']['methodName'])
		{
			return $this->multicall_error('nomethod');
		}

		list($scalar_value, $scalar_type) = array(reset($methName->me), key($methName->me));
		$scalar_type = $scalar_type === $this->xmlrpcI4 ? $this->xmlrpcInt : $scalar_type;

		if ($methName->kindOf() !== 'scalar' OR $scalar_type !== 'string')
		{
			return $this->multicall_error('notstring');
		}
		elseif ($scalar_value === 'system.multicall')
		{
			return $this->multicall_error('recursion');
		}
		elseif ( ! $params = $call->me['struct']['params'])
		{
			return $this->multicall_error('noparams');
		}
		elseif ($params->kindOf() !== 'array')
		{
			return $this->multicall_error('notarray');
		}

		list($b, $a) = array(reset($params->me), key($params->me));

		$msg = new XML_RPC_Message($scalar_value);
		for ($i = 0, $numParams = count($b); $i < $numParams; $i++)
		{
			$msg->params[] = $params->me['array'][$i];
		}

		$result = $this->_execute($msg);

		if ($result->faultCode() !== 0)
		{
			return $this->multicall_error($result);
		}

		return new XML_RPC_Values(array($result->value()), 'array');
	}

}
