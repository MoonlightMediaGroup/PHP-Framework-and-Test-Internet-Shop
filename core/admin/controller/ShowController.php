<?php

namespace core\admin\controller;

class ShowController extends BaseAdmin
{

    protected function inputData () {

        $this->exectBase();

        $this->createTableData();





        echo "<pre>";
        var_dump($this);
        echo "</pre>";
        exit();

    }

    protected function outputData () {



    }

}