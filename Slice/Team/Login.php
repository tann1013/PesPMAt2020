<?php
/**
 * PESCMS for PHP 5.4+
 *
 * Copyright (c) 2014 PESCMS (http://www.pescms.com)
 *
 * For the full copyright and license information, please view
 * the file LICENSE.md that was distributed with this source code.
 * @core version 2.6
 * @version 1.0
 */


namespace Slice\Team;

/**
 * 登录验证切片
 * Class Login
 */
class Login extends \Core\Slice\Slice{

    public function before() {
        if(empty($this->session()->get('team')['user_id'])){
            $url = empty($_SERVER['REQUEST_URI']) ? '' : base64_encode($_SERVER['REQUEST_URI']);
            $this->jump($this->url('Team-Login-index', ['back_url' => $url]));
        }
    }

    public function after() {
    }


}