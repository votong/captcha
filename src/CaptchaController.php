<?php

namespace VoTong\Captcha;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class CaptchaController extends Controller {

	/**
	 * get CAPTCHA
	 *
	 * @param \VoTong\Captcha\Captcha $captcha        	
	 * @param string $config        	
	 * @return ImageManager->response
	 */
	public function getCaptcha(Captcha $captcha, $config = 'default', $formId = false) {
		return $captcha->create($config, $formId);
	}
	
	public function postCheckCaptcha() {
		$rules = ['captcha' => 'required|captcha'];
		$validator = Validator::make(['captcha' => Input::get('captcha')], $rules, ['captcha' => '*Verify code incorrect']);
		
		if ($validator->passes())
		{
			echo 'true';
			die;
		}
		
		echo 'false';
		die;
	}
}
