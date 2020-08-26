<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
// | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
// +----------------------------------------------------------------------

namespace app\index\controller;

use think\admin\Controller;

/**
 * Class Index
 * @package app\index\controller
 */
class Index extends Controller
{
    public function index()
    {
        $this->redirect(sysuri('admin/login/index'));
    }

    public function api()
    {
        $appkey = "ajOqShL3EtTNdnTSMp1JtKchIRlXT7Rr";

        $data = [
            "apikey" => $appkey,
            "tkl"    => "緮置内容₳tezAc2qU8DM₳达开τa0寳【俄罗斯粗毛线戳戳乐针掇花针绣花diy套装加粗铜管工具刺绣针。】",
            "pid"    => "mm_356540186_2002300441_110799050060"
        ];

        $result = $this->http("http://api.tbk.dingdanxia.com/tbk/tkl_privilege", $data, "POST");

        $data = json_decode($result, true);

        $item_url = $data["data"]["item_url"];

        $data = [
            "apikey" => $appkey,
            "text" => "XQY的淘口令",
            "url" => $item_url,
        ];

        $result = $this->http("http://api.tbk.dingdanxia.com/tkl/create", $data, "POST");
        

        return $result;
    }

    function http($url, $params = '', $method = 'GET', $header = [], $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
    //        CURLOPT_HTTPHEADER => $header
        );
        if (!empty($params) && is_array($params)) {
            $http_build_query_params = http_build_query($params);
        }else{
            $http_build_query_params=$params;
        }
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . $http_build_query_params;
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : $http_build_query_params;
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new \Exception('不支持的请求方式！');
        }
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error) throw new \Exception('请求发生错误：' . $error);
        return $data;
    }
}