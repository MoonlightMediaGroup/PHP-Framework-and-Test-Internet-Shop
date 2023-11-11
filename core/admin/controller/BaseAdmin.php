<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\settings\Settings;

abstract class BaseAdmin extends BaseController
{

    protected $model;

    protected $table;
    protected $columns;

    protected $menu;
    protected $title;


    protected function inputData() {

        $this->init(true);

        $this->title = 'Admin Panel';

        if(!$this->model) $this->model = Model::instance();
        if(!$this->menu) $this->menu = Settings::get('projectTables');

        $this->sendNoCacheHeades();

    }

    protected function outputData() {



    }

    protected function sendNoCacheHeades() {
        header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Cache-Control: max-age=0");
        header("Cache-Control: post-check=0, pre-check=0");
    }

    protected function exectBase() {
        self::inputData();
    }

    protected function createTableData() {

        if(!$this->table) {
            //31 35min
        }

    }

}