<?php

namespace core\plugins\shop;

use core\base\controller\BaseController;

class IndexController extends BaseController
{
	protected $name;

	protected function inputData () {

		var_dump($this->isPost());
		exit();
	}
}