<?php
namespace TaJaeger;

use Jaeger\Config;
use GuzzleHttp\Client;
use OpenTracing\Formats;

class CreateTrace {
    public static $instance = null;
    public static $agentHost = "";
    public static $env = "";
    public static $appName = "";


    public function __construct(){
    }

    public static function getJaegerAgentSVC(string $env) : string {
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

    public function loadConfig(string $env, string $appName) {
        self::$appName = $appName;
        self::$env = $env;
    }

    public static function uploadData(array $server)
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
        // 开始一个span
        $clientSpan = $tracer->startSpan(self::$appName);
        // 把定义好的span数据注入到injectTarget
        $injectTarget = [];
        $tracer->inject($clientSpan->getContext(), Formats\TEXT_MAP, $injectTarget);

        $method = 'GET';
        $url = 'http://testtracing/'; // 服务端URL

        // 定义一个client
        $client = new Client();
        // 发送请求
        $res = $client->request($method, $url,['headers' => $injectTarget]);

        $clientSpan->setTag('http.status_code', 200);
        $clientSpan->setTag('http.method', 'GET');
        $clientSpan->setTag('http.url', $url);

        $clientSpan->log(['message' => "HTTP1 ". $method .' '. $url .' end !']);
        // 结束client span内容
        $clientSpan->finish();

        // 往链路提交span数据
        $config->flush();

        return self::$instance;
    }
}