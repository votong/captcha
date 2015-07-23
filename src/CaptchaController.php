<?php

namespace VoTong\Captcha;

use Illuminate\Routing\Controller;

class CaptchaController extends Controller
{

    /**
     * get CAPTCHA
     *
     * @param \VoTong\Captcha\Captcha $captcha
     * @param string $config
     * @return ImageManager->response
     */
    public function getCaptcha(Captcha $captcha, $config = 'default')
    {
        return $captcha->create($config);
    }

}
