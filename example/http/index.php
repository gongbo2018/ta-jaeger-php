<?php
require_once __DIR__.'/vendor/autoload.php';

use TaJaeger\CreateTrace;


CreateTrace::loadConfig("dev", "testName");


CreateTrace::uploadData("appName1", ["http.status_code" => 200, "http.method" => "post"], ["log.message" => "message info"]
);


CreateTrace::uploadData("appName2", ["http.status_code" => 404, "http.method" => "post"], ["log.message" => "message info"]
);

CreateTrace::uploadData(
    "appName3",
    ["http.status_code" => 500, "http.method" => "post", "db.statement" => "SELECT * FROM wuser_table"],
    ["log.message" => "message info"]
);