<?php namespace VoTong\Captcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VoTong\Captcha
 */
class Captcha extends Facade {

    protected static function getFacadeAccessor() { return 'captcha'; }

}
