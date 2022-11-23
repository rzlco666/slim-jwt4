<?php

declare(strict_types=1);

use App\Application\Middleware\SessionMiddleware;
use Slim\App;
use Tuupola\Middleware\JwtAuthentication;

return function (App $app) {
    $app->add(SessionMiddleware::class);

    //Middleware untuk validasi token JWT
    $app->add(new JwtAuthentication([
        "path" => "/api", /* or ["/api", "/admin"] */
        "secure" => false,
        "attribute" => "decoded_token_data",
        "secret" => "supersecretkeyyoushouldnotcommittogithub",
        "algorithm" => ["HS256"],
        "error" => function ($req, $res, $args) {
            $data["status"] = "error";
            $data["message"] = $args["message"];
            return $res
                ->withHeader("Content-Type", "application/json")
                ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    ]));
};
