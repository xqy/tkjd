<?php

// +----------------------------------------------------------------------
// | ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/ThinkLibrary
// | github 代码仓库：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

namespace think\admin\service;

use think\admin\extend\HttpExtend;
use think\admin\Service;

/**
 * 新助通短信接口服务
 * Class ZtSmsService
 * @package think\admin\service
 */
class ZtSmsService extends Service
{
    /**
     * 子账号名称
     * @var string
     */
    protected $username;

    /**
     * 子账号密码
     * @var string
     */
    protected $password;

    /**
     * 短信服务初始化
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function initialize()
    {
        $this->username = sysconf('ztsms.username');
        $this->password = sysconf('ztsms.password');
    }

    /**
     * 短信服务初始化
     * @param string $username 账号名称
     * @param string $password 账号密码
     * @return static
     */
    public function make(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    /**
     * 验证手机短信验证码
     * @param string $code 验证码
     * @param string $phone 手机号验证
     * @param string $tplcode
     * @return boolean
     */
    public function checkVerifyCode(string $code, string $phone, string $tplcode = 'ztsms.register_verify'): bool
    {
        $cache = $this->app->cache->get($ckey = md5("code-{$tplcode}-{$phone}"), []);
        if (is_array($cache) && isset($cache['code']) && $cache['code'] == $code) {
            $this->app->cache->delete($ckey);
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证手机短信验证码
     * @param string $phone 手机号码
     * @param integer $wait 等待时间
     * @param string $tplcode 模板编号
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function sendVerifyCode(string $phone, int $wait = 120, string $tplcode = 'ztsms.register_verify'): array
    {
        $content = sysconf($tplcode) ?: '您的短信验证码为{code}，请在十分钟内完成操作！';
        $cache = $this->app->cache->get($ckey = md5("code-{$tplcode}-{$phone}"), []);
        // 检查是否已经发送
        if (is_array($cache) && isset($cache['time']) && $cache['time'] > time() - $wait) {
            $dtime = ($cache['time'] + $wait < time()) ? 0 : ($wait - time() + $cache['time']);
            return [1, '短信验证码已经发送！', ['time' => $dtime]];
        }
        // 生成新的验证码
        [$code, $time] = [rand(100000, 999999), time()];
        $this->app->cache->set($ckey, ['code' => $code, 'time' => $time], 600);
        // 尝试发送短信内容
        [$state] = $this->timeSend($phone, preg_replace_callback("|{(.*?)}|", function ($matches) use ($code) {
            return $matches[1] === 'code' ? $code : $matches[1];
        }, $content));
        if ($state) return [1, '短信验证码发送成功！', [
            'time' => ($time + $wait < time()) ? 0 : ($wait - time() + $time)],
        ]; else {
            $this->app->cache->delete($ckey);
            return [0, '短信发送失败，请稍候再试！', []];
        }
    }

    /**
     * 创建短信签名
     * @param array $signs 签名列表
     * @param string $remark 签名备注
     * @return array
     */
    public function signAdd(array $signs = [], string $remark = ''): array
    {
        foreach ($signs as $key => $sign) {
            if (strpos($sign, '】') === false) $signs[$key] = $sign . '】';
            if (strpos($sign, '【') === false) $signs[$key] = '【' . $sign;
        }
        $data = ['sign' => $signs, 'remark' => $remark];
        return $this->doRequest('https://api.mix2.zthysms.com/sms/v1/sign', $data);
    }

    /**
     * 查询短信签名
     * @param string $sign 短信签名
     * @return array
     */
    public function signGet(string $sign): array
    {
        if (strpos($sign, '】') === false) $sign = $sign . '】';
        if (strpos($sign, '【') === false) $sign = '【' . $sign;
        return $this->doRequest('https://api.mix2.zthysms.com/sms/v1/sign/query', ['sign' => $sign]);
    }

    /**
     * 报备短信模板
     * @param string $temName 模板名称
     * @param integer $temType 模板类型（1验证码,2行业通知,3营销推广）
     * @param string $temContent 模板内容
     * @param array $paramJson 变量格式
     * @param string $remark 模板备注
     * @return array
     */
    public function tplAdd(string $temName, int $temType, string $temContent, array $paramJson = [], string $remark = ''): array
    {
        return $this->doRequest('https://api.mix2.zthysms.com/sms/v2/template', [
            'temName' => $temName, 'temType' => $temType, 'temContent' => $temContent, 'paramJson' => $paramJson, 'remark' => $remark,
        ]);
    }

    /**
     * 查询模板状态
     * @param string $temId 短信模板
     * @return array
     */
    public function tplGet(string $temId): array
    {
        return $this->doRequest('https://api.mix2.zthysms.com/sms/v2/template/query', ['temId' => $temId]);
    }

    /**
     * 发送模板短信
     * @param string $tpId 短信模板
     * @param string $sign 短信签名
     * @param array $records 发送记录
     * @return array
     */
    public function tplSend(string $tpId, string $sign, array $records): array
    {
        if (strpos($sign, '】') === false) $sign = $sign . '】';
        if (strpos($sign, '【') === false) $sign = '【' . $sign;
        return $this->doRequest('https://api.mix2.zthysms.com/v2/sendSmsTp', [
            'tpId' => $tpId, 'records' => $records, 'signature' => $sign,
        ]);
    }

    /**
     * 发送定时短信
     * @param string $mobile 发送手机号码
     * @param string $content 发送短信内容
     * @param string $time 定时发送时间（为 null 立即发送）
     * @return array
     */
    public function timeSend(string $mobile, string $content, $time = null): array
    {
        $data = ['mobile' => $mobile, 'content' => $content];
        if ($time > 0) $data['time'] = $time;
        return $this->doRequest('https://api.mix2.zthysms.com/v2/sendSms', $data);
    }

    /**
     * 批量发送短信
     * @param array $records
     * @return array
     */
    public function batchSend(array $records): array
    {
        return $this->doRequest('https://api.mix2.zthysms.com/v2/sendSmsPa', ['records' => $records]);
    }

    /**
     * 短信条数查询
     */
    public function balance(): array
    {
        [$state, $result, $message] = $this->doRequest('https://api.mix2.zthysms.com/v2/balance', []);
        return [$state, $state ? $result['sumSms'] : 0, $message];
    }

    /**
     * 执行网络请求
     * @param string $url 接口请求地址
     * @param array $data 接口请求参数
     * @return array
     */
    private function doRequest(string $url, array $data): array
    {
        $encode = md5(md5($this->password) . ($tkey = time()));
        $options = ['headers' => ['Content-Type:application/json;charset="UTF-8"']];
        $extends = ['username' => $this->username, 'password' => $encode, 'tKey' => $tkey];
        $result = json_decode(HttpExtend::post($url, json_encode(array_merge($data, $extends)), $options), true);
        if (empty($result['code'])) {
            return [0, [], '接口请求网络异常'];
        } elseif (intval($result['code']) === 200) {
            return [1, $result, $this->error($result['code'])];
        } else {
            return [0, $result, $this->error($result['code'])];
        }
    }

    /**
     * 获取状态描述
     * @param integer $code 异常编号
     * @return string
     */
    private function error(int $code): string
    {
        $arrs = [
            200  => '提交成功',
            4001 => '用户名错误',
            4002 => '密码不能为空',
            4003 => '短信内容不能为空',
            4004 => '手机号码错误',
            4006 => 'IP鉴权错误',
            4007 => '用户禁用',
            4008 => 'tKey错误',
            4009 => '密码错误',
            4011 => '请求错误',
            4013 => '定时时间错误',
            4014 => '模板错误',
            4015 => '扩展号错误',
            4019 => '用户类型错误',
            4023 => '签名错误',
            4025 => '模板变量内容为空',
            4026 => '手机号码数最大2000个',
            4027 => '模板变量内容最大200组',
            4029 => '请使用 POST 请求',
            4030 => 'Content-Type 请使用 application/json',
            4031 => '模板名称不能为空',
            4032 => '模板类型不正确',
            4034 => '模板内容不能为空',
            4035 => '模板名称已经存在',
            4036 => '添加模板信息失败',
            4037 => '模板名称最大20字符',
            4038 => '模板内容超过最大字符数',
            4040 => '模板内容缺少变量值或规则错误',
            4041 => '模板内容中变量规范错误',
            4042 => '模板变量个数超限',
            4044 => '接口24小时限制提交次数超限',
            9998 => 'JSON解析错误',
            9999 => '非法请求',
        ];
        return $arrs[$code] ?? $code;
    }
}