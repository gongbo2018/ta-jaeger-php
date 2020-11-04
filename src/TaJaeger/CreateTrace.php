<?php
namespace TaJaeger;

use Jaeger\Config;
use OpenTracing\Formats;

class CreateTrace {
    public static $instance = null;
    public static $agentHost = "";
    public static $env = "";
    public static $appName = "";


    public function __construct(){
    }

    /**
     * 获取提供服务主机
     *
     * @param string $env
     * @return string
     */
    public static function getJaegerAgentSVC(string $env) {
        if (self::$agentHost != "") {
            return self::$agentHost;
        }

        switch ($env){
            case "dev" :
                self::$agentHost = "172.17.16.189:6831";
                break;
            case "testing":
                self::$agentHost = "0.0.0.0:6831";
                break;
            case "pro":
                self::$agentHost = ":6831";
                break;
            default:
                self::$agentHost = "172.17.16.189:6831";
        }

        return self::$agentHost;
    }

    /**
     * 初始化配置文件
     *
     * @param string $env
     * @param string $appName
     */
    public static function loadConfig(string $env, string $appName) {
        self::$appName = $appName;
        self::$env = $env;
    }

    /**
     * @param string $spanName
     * @param array $tag [key => val]
     * @param array $log [key => val]
     * @return CreateTrace|null
     *
     * @throws \Exception
     */
    public static function uploadData(string $spanName, array $tag = [], array $log = [])
    {
        if(! (self::$instance instanceof self) )
        {
            self::$instance = new self();
        }

        //init client span start
        $config = Config::getInstance();
        $config->gen128bit();

        // 初始化链路
        $tracer = $config->initTracer(self::$appName, self::getJaegerAgentSVC(self::$env));

        // 展开span
        $spanContext = null;
        $all_header = getallheaders();
        $spanContext = $tracer->extract(Formats\TEXT_MAP, $all_header);

        if ($spanContext != null) {
            $clientSpan = $tracer->startSpan($spanName, ['child_of' => $spanContext]);
        } else {
            $clientSpan = $tracer->startSpan($spanName);
        }

        // 赋值tag
        if (!empty($tag)) {
            foreach ($tag as $k => $v) {
                $clientSpan->setTag($k, $v);
            }
        }

        // 赋值log
        if (!empty($log)) {
            foreach ($log as $k => $v) {
                $clientSpan->log([$k => $v]);
            }
        }

        // 结束client span内容
        $clientSpan->finish();

        // 往链路提交span数据
        $config->flush();

        return self::$instance;
    }

}