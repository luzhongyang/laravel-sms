<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/7/6
 * Time: 13:39
 */

namespace Toplan\Sms;


class FcuiAgent extends Agent
{

    /**
     * sms send process entry
     * @param       $tempId
     * @param       $to
     * @param array $data
     * @param       $content
     *
     * @return mixed
     */
    public function sendSms($tempId, $to, Array $data, $content)
    {
        $this->sendContentSms($to, $content);
    }

    /**
     * content sms send process
     * @param $to
     * @param $content
     *
     * @return mixed
     */
    public function sendContentSms($to, $content)
    {
        $url = 'http://124.172.250.160/WebService.asmx/mt';
        $postString = 'Sn=' . \Config::get('sms.sn')
            . '&Pwd=' . \Config::get('sms.pwd')
            . '&mobile=' . $to
            . '&content=' . $content;

        $response = $this->sockPost($url, $postString);
        $responseParse = (array)simplexml_load_string($response);
        if (isset($responseParse) && $responseParse[0] == '0') {
            $this->result['success'] = true;
        }

        $this->result['info'] = $response;
        $this->result['code'] = $responseParse[0];
    }

    /**
     * template sms send process
     * @param       $tempId
     * @param       $to
     * @param array $data
     *
     * @return mixed
     */
    public function sendTemplateSms($tempId, $to, Array $data)
    {
        return null;
    }
}