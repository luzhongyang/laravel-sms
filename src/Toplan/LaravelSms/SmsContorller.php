<?php namespace Toplan\Sms;

use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Http\Request;
use \SmsManager;
use \Validator;
use Illuminate\Routing\Controller;

class SmsController extends Controller {

    public $smsModel;

    public function __construct()
    {
        $this->smsModel = config('laravel-sms.smsModel', 'Toplan/Sms/Sms');
    }

    public function postSendCode($rule, $mobile = '', Request $request)
    {
        $vars = [];
        $input = [
            'mobile' => $mobile,
            'phone' => $mobile,
            'captcha' => $request->input('captcha')
        ];
        $vars['success'] = false;
        //验证手机号合法性-------------------------------
        //设置手机号验证规则

        if (SmsManager::hasRule('mobile', $rule)) {
            SmsManager::rule('mobile', $rule);
        }

        $validator = Validator::make($input, [
            'phone' => ['required', 'mobile', SmsManager::getRule('mobile')],
            'captcha' => SmsManager::getRule('captcha')
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), '422');
        }
        //------------------------------------------

        // 发送短信----------------------------------
        $code      = SmsManager::generateCode();
        $minutes   = SmsManager::getCodeValidTime();
        $tempIdArray = SmsManager::getVerifySmsTemplateIdArray();
        $template  = SmsManager::getVerifySmsContent();
        $content   = vsprintf($template, [$code, $minutes]);
        $sms       = new $this->smsModel;
        $result    = $sms->template($tempIdArray)
                         ->to($mobile)
                         ->data(['code' => $code,'minutes' => $minutes])
                         ->content($content)
                         ->send();
        if ($result) {
            $data = SmsManager::getSmsData();
            $data['sent'] = true;
            $data['mobile'] = $mobile;
            $data['code'] = $code;
            $data['deadline_time'] = time() + ($minutes * 60);
            SmsManager::storeSmsDataToSession($data);
            $vars['success'] = true;
            $vars['msg'] = '短信发送成功，请注意查收';
            $vars['type'] = 'sent_success';
        } else {
            $vars['msg'] = '短信发送失败，请重新获取';
            $vars['type'] = 'sent_failed';
        }
        return response()->json($vars);
    }

    public function getInfo()
    {
        $html = '<h2 align="center" style="margin-top: 20px;">Hello, welcome to laravel-sms for l5.</h2>';
        $html .= '<p style="color: #666;"><a href="https://github.com/toplan/laravel-sms" target="_blank">laravel-sms源码</a>托管在GitHub，欢迎你的使用。如有问题和建议，欢迎提供issue。当然你也能为该项目提供开源代码，让laravel-sms支持更多服务商。</p>';
        $html .= '<hr>';
        $html .= '<p>你可以在调试模式(设置config/app.php中的debug为true)下查看到存储在session中的验证码短信相关数据(方便你进行调试)：</p>';
        echo $html;
        if (config('app.debug')) {
            dd(SmsManager::getSmsDataFromSession());
        } else {
            echo '<p align="center" style="color: #ff0000;;">现在是非调试模式，无法查看验证码短信数据</p>';
        }
    }

}