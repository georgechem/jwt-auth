# JWT token library
<hr>

Use composer to install:
```php
composer require georgechem/jwt-auth
```

In root directory of your project create .env File like this
```dotenv
SERVER_SECRET='your server secret'
TOKEN_EXPIRE='5 minutes' // use time according to your needs
SERVER_DOMAIN = 'example.com' // server domain
HEADER_NAME='jwt-token' //name of header where jwt will be put
```

To generate token:
in your entry point, generally index.php but can be any .php file

>To obtain token do POST request to entry point with following data:
```php
$_POST['email'] and $_POST['password'] // data used internally to generate JWT
```

```php
require __DIR__ . '/vendor/autoload.php';
$jwt = Jwt::getInstance();
// echo json response which can be consumed in javascript
$jwt->generate()->jsonResponse();
```

To verify token and authenticate/authorize user in entry point:
```php
$jwt = Jwt::getInstance();
/**
 * Token verified successfully|fail
 * array[optional] may contain additional options for verifications
 * like: user role, server name etc...
 * @Return bool
 */ 
$jwt->verify(array());
```

## Exemplary usage:

>Obtain token for new or already registered user:
```php
use Georgechem\JwtAuth\Jwt\Jwt;

require __DIR__ . '/vendor/autoload.php';

// coming from traditional form or javascript
$_POST['email'] = 'user@email.com';
$_POST['password'] = 'user_password';

$jwt = Jwt::getInstance();
// json response may be consumed by javascript and token can be stored 
// in local storage
$jwt->generate()->jsonResponse();
```

>Verify token for request
```php
use Georgechem\JwtAuth\Jwt\Jwt;

require __DIR__ . '/vendor/autoload.php';

$jwt = Jwt::getInstance();
$_SERVER['jwt-token'] = 'that.token.should_be_from.header';
// if token is valid (not expired or malformed etc.)
if($jwt->verify()){
    //can use token data to do additional security checks manually
    var_dump($jwt->tokenData());
}
```

