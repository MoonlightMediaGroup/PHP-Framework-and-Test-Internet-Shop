<?php

namespace core\base\settings;

use core\base\controller\Singleton;

class Settings 
{
	use Singleton;

	private $routes = [
		'admin' => [
			'alias' => 'admin',
			'path' => 'core/admin/controller/',
			'hrUrl' => false,
			'routes' => []
		],
		'settings' => [
			'path' => 'core/base/settings/'
		],
		'plugins' => [
			'path' => 'core/plugins/',
			'hrUrl' => false,
			'dir' => false,
		],
		'user' => [
			'path' => 'core/user/controller/',
			'hrUrl' => true,
			'routes' => [
				
			]
		],
		'default' => [
			'controller' => 'IndexController',
			'inputMethod' => 'inputData',
			'outputMethod' => 'outputData'
		]
	];

	private $templateArr = [
		'text' => ['name','phone','adress'],
		'textarea' => ['content','keywords'],
	];

	static public function get($property) {
		return self::instance()->$property;
	}

	public function clueProperties($class_name) {
		$baseProperties = [];

		foreach ($this as $name => $item) {
			$property = $class_name::get($name);
			
			if(is_array($property) && is_array($item)) {
				$baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
				continue;
			}

			if(!$property) $baseProperties[$name] = $this->$name;
		}

		return $baseProperties;
	}

	//Function gluing arrays
	public function arrayMergeRecursive() {
		$arrays = array();
		for($i = 0;$i < func_num_args();$i++) array_push($arrays,func_get_arg($i));

		$base = array_shift($arrays);

		foreach ($arrays as $array) {
			foreach ($array as $key => $value) {
				if(is_array($value) && is_array($base[$key])) {
					$base[$key] = $this->arrayMergeRecursive($base[$key], $value);
				}else{
					if(is_int($key)){
						if(!in_array($value, $base)) array_push($base, $value);
						continue;
					}
					$base[$key] = $value;
				}
			}
		}

		return $base;
	}
}