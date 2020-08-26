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

namespace app\admin\controller\api;

use think\admin\Controller;
use think\admin\service\InstallService;
use think\admin\service\ModuleService;

/**
 * 安装服务端支持
 * Class Update
 * @package app\admin\controller\api
 */
class Update extends Controller
{

    /**
     * 读取文件内容
     */
    public function get()
    {
        $filename = decode(input('encode', '0'));
        if (!ModuleService::instance()->checkAllowDownload($filename)) {
            $this->error('下载的文件不在认证规则中！');
        }
        if (file_exists($realname = $this->app->getRootPath() . $filename)) {
            $this->success('读取文件内容成功！', [
                'content' => base64_encode(file_get_contents($realname)),
            ]);
        } else {
            $this->error('读取文件内容失败！');
        }
    }

    /**
     * 读取文件列表
     */
    public function node()
    {
        $this->success('获取文件列表成功！', InstallService::instance()->getList(
            json_decode($this->request->post('rules', '[]', ''), true),
            json_decode($this->request->post('ignore', '[]', ''), true)
        ));
    }

    /**
     * 获取模块信息
     */
    public function version()
    {
        $this->success('获取模块信息成功！', ModuleService::instance()->getModules());
    }

}