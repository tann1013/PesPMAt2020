<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.8
 * @version 1.0
 */

namespace App\Team\GET;

class Menu extends Content{

    public function index($display = true){
        $this->assign('title', $this->model['model_title']);
        $this->assign('field', $this->field);
        $this->layout();
    }

    public function action($display = true){
        $this->assign('topMenu', json_encode(\Model\Menu::topMenu()));
        parent::action();
    }

}