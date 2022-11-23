<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->post('/login', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $Username = trim(strip_tags($input['Username']));
        $Password = trim(strip_tags($input['Password']));
        $sql = "SELECT IdUser, Username  FROM `user` WHERE Username=:Username AND `Password`=:Password";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("Username", $Username);
        $sth->bindParam("Password", $Password);
        $sth->execute();
        $user = $sth->fetchObject();
        if (!$user) {
            return $this->response->withJson(['status' => 'error', 'message' => 'These credentials do not match our records username.']);
        }
        $settings = $this->get('settings');
        $token = [
            'IdUser' => $user->IdUser,
            'Username' => $user->Username
        ];
        $token = JWT::encode($token, $settings['jwt']['secret'], "HS256");
        return $this->response->withJson(['status' => 'success', 'data' => $user, 'token' => $token]);
    });

    $app->post('/register', function (Request $request, Response $response, array $args) {
        $input = $request->getParsedBody();
        $Username = trim(strip_tags($input['Username']));
        $NamaLengkap = trim(strip_tags($input['NamaLengkap']));
        $Email = trim(strip_tags($input['Email']));
        $NoHp = trim(strip_tags($input['NoHp']));
        $Password = trim(strip_tags($input['Password']));
        $sql = "INSERT INTO user(Username, NamaLengkap, Email, NoHp, Password) 
            VALUES(:Username, :NamaLengkap, :Email, :NoHp, :Password)";
        $sth = $this->db->prepare($sql);
        $sth->bindParam("Username", $Username);
        $sth->bindParam("NamaLengkap", $NamaLengkap);
        $sth->bindParam("Email", $Email);
        $sth->bindParam("NoHp", $NoHp);
        $sth->bindParam("Password", $Password);
        $StatusInsert = $sth->execute();
        if ($StatusInsert) {
            $IdUser = $this->db->lastInsertId();
            $settings = $this->get('settings');
            $token = [
                'IdUser' => $IdUser,
                'Username' => $Username
            ];
            $token = JWT::encode($token, $settings['jwt']['secret'], "HS256");
            $dataUser = [
                'IdUser' => $IdUser,
                'Username' => $Username
            ];
            return $this->response->withJson(['status' => 'success', 'data' => $dataUser, 'token' => $token]);
        } else {
            return $this->response->withJson(['status' => 'error', 'data' => 'error insert user.']);
        }
    });

    $app->group('/api', function (Group $group) {
        $group->get("/user/{IdUser}", function (Request $request, Response $response, array $args) {
            $IdUser = $args["IdUser"];
            $sql = "SELECT IdUser, Username, NamaLengkap, Email, NoHp FROM `user` WHERE IdUser=:IdUser";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam("IdUser", $IdUser);
            $stmt->execute();
            $mainCount = $stmt->rowCount();
            $result = $stmt->fetchObject();
            if ($mainCount == 0) {
                return $this->response->withJson(['status' => 'error', 'message' => 'no result data.']);
            }
            return $response->withJson(["status" => "success", "data" => $result], 200);
        });
    });
};
