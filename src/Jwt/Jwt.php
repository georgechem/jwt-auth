<?php

namespace Georgechem\JwtAuth\Jwt;

use Dotenv\Dotenv;
use Exception;

class Jwt
{
    private static ?Jwt $instance = null;
    private ?string $secret = null;
    private ?string $valid = null;
    private ?string $server = null;
    private \DateTimeImmutable $issuedAt;
    private false|\DateTimeImmutable $expire;
    private array $payload = [];
    private ?string $tokenId = null;
    private ?string $token = null;
    /**
     * @var mixed|string
     */
    private ?string $email = null;

    private function __construct()
    {
        $this->init();
        $this->initVariables();

    }

    public static function getInstance(): ?Jwt
    {
        if(!self::$instance){
            self::$instance = new Jwt();
        }
        return self::$instance;
    }


    public function generate():string
    {
        try{
            if(!$this->isMethodAllowed()){
                throw new Exception('Method not allowed');
            }
            if(!$this->areDataProvided()){
                throw new Exception('Required data are not provided');
            }
            if(!$this->validateCredentialsAgainstDb()){
                throw new Exception('User with such credentials does not exist');
            }
        }catch(Exception $e){
            echo $e->getMessage();
            exit;
        }

        $this->prepare();

        return $this->getToken();

    }

    private function init()
    {
        $this->dotEnvInit();
    }

    private function initVariables()
    {
        $this->secret = $_ENV['SERVER_SECRET'];
        $this->valid = $_ENV['TOKEN_EXPIRE'];
        $this->server = $_ENV['SERVER_NAME'];
    }

    private function dotEnvInit()
    {
        $path = dirname(__DIR__, 3);
        $isPathFromComposer = str_ends_with($path, '/georgechem');
        print_r($path);
        if($isPathFromComposer){
            $path = dirname(__DIR__, 2);
        }else{
            $path = dirname(__DIR__, 5);
        }

        $dotenv = Dotenv::createImmutable($path);
        $dotenv->load();
    }

    private function isMethodAllowed():bool
    {
        if(!empty($_SERVER['REQUEST_METHOD'])){
            if($_SERVER['REQUEST_METHOD'] === 'POST'){
                return true;
            }
        }
        // TODO swap logic - testing
        return true;
        //return false;

    }

    private function areDataProvided():bool
    {
        if(empty($_POST['email']) || empty($_POST['password'])){
            // TODO swap logic - testing
            return true;
            //return false;
        }
        return true;
    }

    private function validateCredentialsAgainstDb():bool
    {
        // TODO
        $this->email = $_POST['email'] ?? 'test@o2.pl';
        $email = filter_var($this->email, FILTER_VALIDATE_EMAIL,[]);
        $password = $_POST['password'] ?? 'test';

        return true;
    }

    private function prepare()
    {
        try {
            $this->tokenId = base64_encode(random_bytes(16));
        }catch(Exception $e){
            echo $e->getMessage();
            exit;
        }

        $this->issuedAt = new \DateTimeImmutable();
        $this->expire = $this->issuedAt->modify('+' . $this->valid);
        $this->payload = [
            'iat' => $this->issuedAt->getTimestamp(),
            'jti' => $this->tokenId,
            'iss' => $this->server,
            'nbf' => $this->issuedAt->getTimestamp(),
            'exp' => $this->expire->getTimestamp(),
            'data' => [
                'email' => $this->email,
                'role' => 'user'
            ]
        ];
    }

    private function getToken()
    {
        try {
            $this->token = \Firebase\JWT\JWT::encode($this->payload, $this->secret, 'HS512');
        }catch(Exception $e){
            echo $e->getMessage();
            exit;
        }

        return $this->token;
    }



}