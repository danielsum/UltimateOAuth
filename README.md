UltimateOAuth
=============
*A __highly advanced__ Twitter library in PHP.*

[日本語](https://github.com/Certainist/UltimateOAuth/blob/master/README-Japanese.md)

@Version: 5.1.0  
@Author : CertaiN  
@License: FreeBSD  
@GitHub : http://github.com/certainist  


## \[System Requirements\]
- Requires PHP **5.2.0** or later.
- Not depends on **cURL**.
- Not depends on any other files.
- Supports both UNIX and Windows.


## \[Supported Classes and Methods\]

### UltimateOAuth

```php
$uo = new UltimateOAuth( $consumer_key, $consumer_secret, $access_token="", $access_token_secret="" );

(stdClass|Array)      $uo->get                   ( $endpoint,                $params=array()                      );
(stdClass|      Void) $uo->post                  ( $endpoint,                $params=array(), $wait_response=true );
(stdClass|Array|Void) $uo->OAuthRequest          ( $endpoint, $method="GET", $params=array(), $wait_response=true );
(stdClass|      Void) $uo->OAuthRequestMultipart ( $endpoint,                $params=array(), $wait_response=true );

(stdClass) $uo->directGetToken ( $username, $password );

(String) $uo->getAuthorizeURL    ( $force_login=false );
(String) $uo->getAuthenticateURL ( $force_login=false );
```

### UltimateOAuthMulti

```php
$uom = new UltimateOAuthMulti;

(Void)  $uom->enqueue ( &$uo, $method, $arg1, $arg2, $arg3, ... );
(Array) $uom->execute ();
```

### UltimateOAuthRotate

**UltimateOAuthRotate** supports `get`, `post`, `OAuthRequest`, `OAuthRequestMultipart` of **UltimateOAuth**, by using **__call()**.

```php
$uor = new UltimateOAuthRotate;

(mixed)              $uor->__call       ( $name, $arguments );

(Bool)               $uor->register     ( $name, $consumer_key, $consumer_secret );
(Bool|Array)         $uor->login        ( $username, $password, $return_array=false );
(Bool)               $uor->setCurrent   ( $name );
(UltimateOAuth|Bool) $uor->getInstance  ( $name );
(Array)              $uor->getInstances ( );
```

------------------------------------------------------------------

0. Multiple Request Settings
-----------------------

Edit constants of **UltimateOAuthConfig**.

- For the environment that proc_open() is disabled for security reasons
  
  > - `USE_PROC_OPEN`: **FALSE**
  > - `FULL_URL_TO_THIS_FILE`: Not an absolute path, but an **absolute URL**.
  
- Otherwise
  
  > - `USE_PROC_OPEN`: **TRUE** (default)
  

1. OAuth Authentication
-----------------------

To authenticate or authorize, take the following steps.

**prepare.php**

```php
<?php

// Load this library
require_once('UltimateOAuth.php');

// Start session
session_start();

// Create a new UltimateOAuth instance and set it into the session
$_SESSION['uo'] = new UltimateOAuth('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET');
$uo = $_SESSION['uo'];

// Get request_token
$res = $uo->post('oauth/request_token');
if (isset($res->errors)) {
    die(sprintf('Error[%d]: %s',
        $res->errors[0]->code,
        $res->errors[0]->message
    ));
}

// If you want to AUTHENTICATE,
$url = $uo->getAuthenticateURL();
// If you want to AUTHORIZE,
// $url = $uo->getAuthorizeURL();

// Jump to Twitter
header('Location: '.$url);
exit();
```

After user has logined, the page will be jumped back to **Callback URL** with **oauth_verifier**.  
You have to configure this parameter in [Twitter Developers](https://dev.twitter.com/apps).

**callback.php**

```php
<?php

// Load this library
require_once('UltimateOAuth.php');

// Start session
session_start();

// Check session timeout
if (!isset($_SESSION['uo'])) {
    die('Error[-1]: Session timeout.');
}
$uo = $_SESSION['uo'];

// Check oauth_verifier
if (!isset($_GET['oauth_verifier']) || !is_string($_GET['oauth_verifier'])) {
    die('Error[-1]: No oauth_verifier');
}

// Get access_token
$res = $uo->post('oauth/access_token', array(
    'oauth_verifier' => $_GET['oauth_verifier']
));
if (isset($res->errors)) {
    die(sprintf('Error[%d]: %s',
        $res->errors[0]->code,
        $res->errors[0]->message
    ));
}

// Jump to your main page
header('Location: main.php');
exit();
```


**main.php**

```php
<?php

// Load this library
require_once('UltimateOAuth.php');

// Start session
session_start();

// Check session timeout
if (!isset($_SESSION['uo'])) {
    die('Error[-1]: Session timeout.');
}
$uo = $_SESSION['uo'];

// Let's tweet
$uo->post('statuses/update', 'status=Whohoo, I just tweeted!');
```



**Note1:**  
If you already have `access_token` and `access_token_secret`,  
you can build up by just doing like this:

```php
<?php

require_once('UltimateOAuth.php');

$uo = new UltimateOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secreet);
```

**Note2:**  
For saving authenticated UltimateOAuth object as string, use `serialize()` and `unserialize()`.


------------------------------------------------------------------

2-1. Class Detail - UltimateOAuth
----------------------------------

### UltimateOAuth::__construct()

```php
<?php
$uo = new UltimateOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
```

#### Arguments

- *__$consumer\_key__*, *__$consumer\_secret__*  
  **Required**.
  
- *__$access\_token__*, *__$access\_token__*  
  Not necessary if you authenticate or authorize later.

=========================================

### UltimateOAuth::OAuthRequest()

```php
<?php
$uo->OAuthRequest($endpoints, $method, $params, $wait_response);
```

#### Arguments

- *__$endpoints__*  
  See [API Documentation](https://dev.twitter.com/docs/api/1.1).  
  Examples:  
  `statuses/update`, `1.1/statuses/update`, `https://api.twitter.com/1.1/statuses/update.json`
  
- *__$method__*  
  `GET` or `POST`. Case-insensitive.
  
- *__$params__*  
  **Query String** (Not URL encoded) or **Associative Array**.  
  Examples:
  ```php
  <?php
  $params = 'status=TestTweet';
  $params = array('status' => 'TestTweet');
  ```
  For uploading files:
  ```php
  <?php
  $params = '@image='.$filename;
  $params = array('@image' => $filename);
  $params = array('image' => base64_encode(file_get_contents($filename)));
  ```
  
  **Note:**  
  You can't use this with `statuses/update_with_media`.  
  Use **UltimateOAuth::OAuthRequestMultipart()** instead.
  
- *__$wait\_resposne__*  
  **TRUE** as default.
  See below.

#### Return Value

- If successfully, return decoded JSON.  
  Basically it is returned as **stdClass**.  
  Some endpoints return **Array**.  
  Example: `statuses/home_timeline`, `users/lookup`  
  Some endpoints get **Query String**, but it is parsed and returned as **stdClass**.  
  Example: `oauth/request_token`, `oauth/access_token`
  
- If it failed, return **Error Object**. It has the following structure:
  
  > - `(int) $response->errors[0]->code`  
  >   **HTTP STATUS CODE**. Not an error code.  
  >   All error codes are overwritten with HTTP status codes.  
  >   If a local error occurred, this will be **-1**.
  >   
  > - `(string) $response->errors[0]->message`  
  >   An error message.
  
- If `$wait_response` has been set to FALSE, return **NULL**.


=========================================


### UltimateOAuth::get()<br />UltimateOAuth::post()

```php
<?php
$uo->get($endpoints, $params);
$uo->post($endpoints, $params, $wait_response);
```

Wrapper for **UltimateOAuth::OAuthRequest()**.

=========================================


### UltimateOAuth::OAuthRequestMultipart()

Mainly used for the endpoint `statuses/update_with_media`.

```php
<?php
$uo->OAuthRequestMultipart($endpoints, $params, $wait_response);
```

#### Arguments

- *__$endpoints__*  
  Example: `statuses/update_with_media`
  
- *__$params__*  
  Examples:
  ```php 
  <?php
  $params = '@media[]='.$filename;
  $params = array('@media[]' => $filename);
  $params = array('media[]' => file_get_contents($filename));
  ```
  
- *__$wait\_response__*  
  Same as  **UltimateOAuth::OAuthRequest()**.
  **TRUE** as default.
  
#### Return Value
- Same as **UltimateOAuth::OAuthRequest()**.


=========================================

  
### UltimateOAuth::directGetToken()

This method enables you to use OAuth like **xAuth**.
I named this **para-xAuth** authentication.

```php
<?php
$uo->directGetToken($username, $password);
```

#### Arguments
 
- *__$username__*  
  *screen_name* or E-mail Address.
  
- *__$password__*  
  password.
  
#### Return Value
- Same as **UltimateOAuth::OAuthRequest()** when requesting `oauth/access_token`.


=========================================


### UltimateOAuth::getAuthenticateURL()<br />UltimateOAuth::getAuthorizeURL()

```php
<?php
$uo->getAuthenticateURL($force_login);
$uo->getAuthorizeURL($force_login);
```

#### Arguments

- *__$force\_login__*  
  Whether force logined user to login again.
  **FALSE** as default.
  
#### Return Value

- URL String.

#### Note: What is the difference between *Authenticate* and *Authorize* ?

|                | Authenticate  |  Authorize   |
| -------------: |:---------------:| :-----------:|
| New User       | Jump to Twitter | Jump to Twitter |
| Logined User   | Jump to Twitter, but if you set your application<br /> **__Allow this application to be used to Sign in with Twitter__**, <br />quickly jump back to your callback URL.  |  Jump to Twitter  |


2-2. Class Detail - UltimateOAuthMulti
--------------------------------------

This class enables you to execute multiple request **parallelly**.


=========================================


### UltimateOAuthMulti::__construct()

```php
<?php
$uom = new UltimateOAuthMulti;
```


=========================================


### UltimateOAuthMulti::enqueue()

Enqueue a new job.

```php
<?php
$uom->enqueue($uo, $method, $arg1, $arg2, ...);
```


=========================================


#### Arguments

- *__$uo__*  
  **UltimateOAuth** object. **Passed by reference**.
  
- *__$method__*  
  Example: `post`
  
- *__$arg1__*, *__$arg2__*, *__...__*  
  Example: `'statuses/update', 'status=TestTweet'`

=========================================
  
  
### UltimateOAuthMulti::execute()

Execute All jobs.  
After executing, all queues are dequeued.

```php
<?php
$uom->execute($wait_processes);
```

#### Arguments

- *__$wait\_processes__*  
  Same as *__$wait\_response__* of **UltimateOAuth::OAuthRequest()**.  
  **TRUE** as default.
  
#### Return Value

- Return an **Array**, collection of the results.


=========================================


2-3. Class Detail - UltimateOAuthRotate
---------------------------------------

This class enables you to **avoid API limits** easily.  
Also you can use very useful **secret endpoints**, like:

- `GET activity/about_me`  
  Get activities about me.
- `GET activity/by_friends`  
  Get activities by friends.
- `GET statuses/:id/activity/summary`  
  Get activities about a specified status.
- `GET conversation/show/:id`  
  Get statuses related to a specified status.

- `POST friendships/accept`  
  Accept a specified follower request.
- `POST friendships/deny`  
  Deny a specified follower request.
- `POST friendships/accept_all`  
  Accept all follower requests.

=========================================
  
### UltimateOAuthRotate::__construct()

```php
<?php
$uor = new UltimateOAuthRotate;
```

=========================================

### UltimateOAuthRotate::register()

Register your own application.

```php
<?php
$uor->register($name, $consumer_key, $consumer_secret);
```

#### Arguments

- *__$name__*  
  Any name is okay as long as not duplicate with official applications already registered.  
  Just used for identification.  
  Example: `my_app_01`
- *__$consumer\_key__*
- *__$consumer\_secret__*

#### Return Value

- Return result as **TRUE or FALSE**.


=========================================


### UltimateOAuthRotate::login()

Login with all registered applications.
This method depends on **UltimateOAuthMulti** class.

```php
<?php
$uor->login($username, $password, $return_array);
```

#### Arguments
 
- *__$username__*  
  *screen_name* or E-mail Address.
  
- *__$password__*  
  password.
  
- *__$return\_array__*  
  **FALSE** as default.  
  See below.
  
#### Return Value

- If `$return_array` is FALSE, return result as **TRUE or FALSE**.

- If `$return_array` is TRUE, return an **Array**, collection of the results.


=========================================

### UltimateOAuthRotate::setCurrent()

Select an application for **POST** requesting.
GET requests have nothing to do with this.

```php
<?php
$uor->setCurrent($name);
```

#### Arguments

- *__$name__*  
  Example: `my_app_01`

#### Return Value

- Return result as **TRUE or FALSE**.


=========================================


### UltimateOAuthRotate::getInstance($name)

Get **clone** of specified UltimateOAuth Instance.

```php
<?php
$uor->getInstance($name);
```

#### Arguments

- *__$name__*  
  Example: `my_app_01`
  
#### Return Value

- Return **UltimateOAuth** instance or **FALSE**.

=========================================


### UltimateOAuthRotate::getInstances()

Get **clones** of all UltimateOAuth Instance.

```php
<?php
$uor->getInstances($type);
```

#### Arguments

- *__$type__*  
  __0__ - Return all instances **(Default)**  
  __1__ - Return official instances  
  __2__ - Return original instances
  
#### Return Value

- Return an **Array**, collection of the UltimateOAuth instances.


=========================================


### UltimateOAuthRotate::__call()

Call an **UltimateOAuth** method.

Example:
```php
<?php
$uor->get('statuses/home_timeline');
```