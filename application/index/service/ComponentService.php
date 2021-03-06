<?php

namespace app\index\service;

use think\Exception;
use app\index\model\Component;

class ComponentService extends Service
{
    /**
     * @param $search
     * @param  int  $limit
     * @return Component|array|bool|float|int|mixed|object|\stdClass|\think\Paginator|null
     * @throws \think\exception\DbException
     */
    public function index($search, $limit = 10)
    {
        $where = [];
        $query = Component::where($where);
        $res = $limit ? $query->paginate($limit) : $query->get();
        $data = $limit ? $res['data'] : $res;
        $limit ? $res['data'] = $data : $res = $data;
        return $res;
    }

    /**
     * @param $data
     * @return Component|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save($data)
    {
        $data['js'] = 'component/'.$data['name'].'/index.js';
        $data['css'] = 'component/'.$data['name'].'/index.css';
        $data['plugins'] = 'component/'.$data['name'].'/plugins.json';
        $data['html'] = 'component/'.$data['name'].'/index.html';
        $data['data'] = 'component/'.$data['name'].'/index.json';
        unset($data['filename']);
        $info = Component::where('name', $data['name'])->find();
        if ($info) {
            return false;
        }
        return Component::create($data);
    }

    /**
     * @param $id
     * @param $data
     * @return Component
     */
    public function update($id, $data)
    {
        return Component::where('id', $id)->update($data);
    }

    /**
     * @param $id
     * @return bool|int
     */
    public function delete($id)
    {
        return Component::where('id', $id)->delete();
    }

    /**
     * @param $id
     * @return array
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        return Component::where('id', $id)->find()->toArray();
    }

    /**
     * 解压文件
     *
     * @param  string  $file  解压文件
     * @param  string  $destination  解压目录
     * @return boolean
     */
    public function unzip($file, $destination)
    {
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            return false;
        }
        $zip->extractTo($destination);
        $zip->close();
        return true;
    }

    /**
     * 移动当前组件的vue文件，重置vue引入所有组件的js文件
     *
     * @param  array  $component  组件信息
     * @return boolean
     */
    public function vueComponentJs($component)
    {
        $vuePath = ROOT_PATH.'fornt/src/components/'.ucwords($component['name']).'.vue';
        if (copy(ROOT_PATH.'component/'.$component['name'].'/index.vue', $vuePath)) {
            $components = Component::column('plugins', 'name');
            $installPlugins = $plugins = $head = $names = [];
            $jsStr = $cssStr = $head = '';
            foreach ($components as $name => $pluginPath) {
                $name = ucwords($name);
                $head .= 'import '.$name.' from "./'.$name.'"'."\r\n";

                $names[] = $name;
                $pluginInfo = json_decode(file_get_contents(ROOT_PATH.$pluginPath), true);
                if (!$pluginInfo) {
                    continue;
                }
                foreach ($pluginInfo as $k => $plugin) {
                    if (in_array($k, $installPlugins)) {
                        continue;
                    }
                    if (isset($plugin['js'])) {
                        $jsStr .= "import '{$plugin['js']}'\r\n";
                    }
                    if (isset($plugin['css'])) {
                        $cssStr .= "import '{$plugin['css']}'\r\n";
                    }
                    $installPlugins[] = $k;
                }
            }
            $componentStr = implode(',', $names);
            $content = $head.$jsStr.$cssStr."\r\n";
            $content .= "export default {\r\n";
            $content .= $componentStr."\r\n";
            $content .= "}\r\n\r\n";
            file_put_contents(ROOT_PATH.'fornt/src/components/index.js', $content);
            return true;
        } else {
            return false;
        }
    }

}