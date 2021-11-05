<?php

namespace Georgechem\JwtAuth\Jwt;

use Georgechem\SqliteDb\Model\Users;
use Dotenv\Dotenv;
use Exception;

class Jwt
{
    private static ?Jwt $instance = null;
    private ?string $secret = null;
    private ?string $valid = null;
    private ?string $server = null;
    private array $payload = [];
    private ?string $email = null;
    private ?string $token = null;
    private ?string $header = null;
    private ?object $data = null;

    private function __construct()
    {
        $this->init();
        $this->initVariables();
    }
    /**
     * Singleton
     * @return Jwt|null
     */
    public static function getInstance(): ?Jwt
    {
        if(!self::$instance){
            self::$instance = new Jwt();
        }
        return self::$instance;
    }
    /**
     * Generate token
     * @return self
     */
    public function generate():self
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
            $this->reportError($e->getMessage(), $e->getCode());
            exit;
        }
        $this->prepare();
        $this->token = $this->getToken();
        return $this;
    }
    private function reportError(string $msg = 'Unknown error', int $code = 500):void
    {
        echo json_encode(['code' => $code, 'msg' => $msg]);

    }

    /**
     * Init dotenv storage
     * Details: https://github.com/vlucas/phpdotenv
     */
    private function init()
    {
        $this->dotEnvInit();
    }
    /**
     * Read config from .env
     */
    private function initVariables()
    {
        $this->secret = $_ENV['SERVER_SECRET'];
        $this->valid = $_ENV['TOKEN_EXPIRE'];
        $this->server = $_ENV['SERVER_DOMAIN'];
        $this->header = $_ENV['HEADER_NAME'];
    }
    /**
     * Dotenv init implementation
     */
    private function dotEnvInit()
    {
        $path = dirname(__DIR__, 3);
        $isPathFromComposer = str_ends_with($path, '/georgechem');
        if($isPathFromComposer){
            $path = dirname(__DIR__, 5);
        }else{
            $path = dirname(__DIR__, 2);
        }
        $dotenv = Dotenv::createImmutable($path);
        $dotenv->load();
    }
    /**
     * Check is request method allowed
     * @return bool
     */
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
    /**
     * Check is user provided email & password
     * @return bool
     */
    private function areDataProvided():bool
    {
        if(empty($_POST['email']) || empty($_POST['password'])){
            return false;
        }
        return true;
    }
    /**
     * Check credentials in database
     * @return bool
     */
    private function validateCredentialsAgainstDb():bool
    {
        $this->email = $_POST['email'] ?? null;
        $email = filter_var($this->email, FILTER_VALIDATE_EMAIL,[]);
        $password = $_POST['password'] ?? null;

        $users = new Users();
        //if($email && $password) $users->insert($email, $password);
        if(!$users->isUser($email)) return false;
        return $users->verifyPassword($email, $password);

    }
    /**
     * Prepare data before token generation
     */
    private function prepare()
    {
        $tokenId = null;
        try {
            $tokenId = base64_encode(random_bytes(16));
        }catch(Exception $e){
            $this->reportError($e->getMessage(), $e->getCode());
            exit;
        }
        $issuedAt = new \DateTimeImmutable();
        $expire = $issuedAt->modify('+' . $this->valid);
        $this->payload = [
            'iat' => $issuedAt->getTimestamp(),
            'jti' => $tokenId,
            'iss' => $this->server,
            'nbf' => $issuedAt->getTimestamp(),
            'exp' => $expire->getTimestamp(),
            'data' => [
                'email' => $this->email,
                'role' => 'user'
            ]
        ];
    }

    /**
     * Return Generated token
     * @return string|void|null
     */
    private function getToken()
    {
        try {
            $this->token = \Firebase\JWT\JWT::encode($this->payload, $this->secret, 'HS512');
        }catch(Exception $e){
            $this->reportError($e->getMessage(), $e->getCode());
            exit;
        }
        return $this->token;
    }

    /**
     * Get token
     * @return string|null
     */
    public function token():?string
    {
        return $this->token;
    }

    /**
     * Json response
     */
    public function jsonResponse():void
    {
        // Might add headers
        echo json_encode(['status' => '200', 'jwt' => $this->token]);
    }

    /**
     * Verify token
     * @param array $params
     * @param string|null $token
     * @return bool|void
     */
    public function verify(array $params = [], string $token = null)
    {
        $jwt = $_SERVER[$this->header] ?? $token;

        try {
            $this->data = \Firebase\JWT\JWT::decode((string) $jwt, $this->secret, ['HS512']);
        }catch(Exception $e){
            $this->reportError($e->getMessage(), $e->getCode());
            exit;
        }
        return $this->deepVerification($params);
    }

    /**
     * Get token decoded data
     * @return object|null
     */
    public function tokenData(): ?object
    {
        return $this->data;
    }

    /**
     * Perform deep verification basing on user params
     * ['iss' => 'example.com', 'exp' => timeStamp, 'data' => ['role' => 'user'] ]
     * @param array $params
     * @return bool
     */
    private function deepVerification(array $params):bool
    {
        if(count($params) === 0) return true;

        return false;
    }

}