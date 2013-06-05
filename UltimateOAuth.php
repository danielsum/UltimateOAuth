<?php

/*************************************************/
/***************** UltimateOAuth *****************/
/*************************************************/

/* A highly advanced Twitter library in PHP.
 * 
 * @Version: 5.1.1
 * @Author : CertaiN
 * @License: FreeBSD
 * @GitHub : http://github.com/certainist/UltimateOAuth
 * 
 * Requires PHP **5.2.0** or later.
 * Not depends on **cURL**.
 * Not depends on any other files.
 * Supports both UNIX and Windows.
 */
 
 
 
 
/*  
 *  Configuration
 *
 ******************************
 ******************************
 ******* VERY IMPORTANT *******
 ******************************
 ******************************
 */ 
interface UltimateOAuthConfig {
    
    /*
     *  Multiple request settings.
     *   
     *   TRUE  - use proc_open() - You should select TRUE as long as your server allows.
     *   FALSE - use fsockopen() - For the environment that proc_open() is disabled for security reasons. 
     *
     */
    const USE_PROC_OPEN = true;
    
    // Used if USE_PROC_OPEN == TRUE
    const PHP_COMMAND = 'php';
    
    // Used if USE_PROC_OPEN == FALSE
    const FULL_URL_TO_THIS_FILE     = ''; /* You have to fill here! */
    const MULTIPLE_REQUEST_KEY_NAME = '____ULTIMATE_OAUTH_MULTIPLE_REQUEST_KEY____';
    
    /*
     *  About request URL.
     */
    const DEFAULT_SCHEME               = 'https'           ;
    const DEFAULT_HOST                 = 'api.twitter.com' ;
    const DEFAULT_API_VERSION          = '1.1'             ;
    const DEFAULT_ACTIVITY_API_VERSION = '1.1'             ;
    
    /*
     *  User-Agent for requesting.
     */
    const USER_AGENT = 'UltimateOAuth';

}
 
 
 
 
/*  
 *  UltimateOAuth - Main class.
 *                  If you want to avoid API limits for GET endpoints,
 *                  use UltimateOAuthRotate class instead.
 */ 
class UltimateOAuth {

    /********************/
    /**** Properties ****/
    /********************/
    
    private $consumer_key;
    private $consumer_secret;
    private $access_token;
    private $access_token_secret;
    private $request_token;
    private $request_token_secret;
    private $authenticity_token;
    private $oauth_verifier;
    private $cookie;
    private $last_http_status_code;
    private $last_called_endpoint;
    
    /***************************/
    /**** Interface Methods ****/
    /***************************/
    
    /*
     *  (UltimateOAuth) __construct() - Create a new UltimateOAuth instance.
     */
    public function __construct(
        $consumer_key          = '', // Consumer Key    (Required)  
        $consumer_secret       = '', // Consumer Secret (Required)
        $access_token          = '', // Access Token        (Not necessary if you authenticate/authorize later)
        $access_token_secret   = '', // Access Token Secret (Not necessary if you authenticate/authorize later)
        /*
         * Don't use args below
         */
        $request_token         = '',
        $request_token_secret  = '',
        $oauth_verifier        = '',
        $authenticity_token    = '',
        $cookie                = array(),
        $last_http_status_code = 0,
        $last_called_endpoint  = ''
    ) {
        // Validate arguments and set them as properties
        $this->consumer_key          = UltimateOAuthModule::stringify($consumer_key)               ;
        $this->consumer_secret       = UltimateOAuthModule::stringify($consumer_secret)            ;
        $this->access_token          = UltimateOAuthModule::stringify($access_token)               ;
        $this->access_token_secret   = UltimateOAuthModule::stringify($access_token_secret)        ;
        $this->request_token         = UltimateOAuthModule::stringify($request_token)              ;
        $this->request_token_secret  = UltimateOAuthModule::stringify($request_token_secret)       ;
        $this->oauth_verifier        = UltimateOAuthModule::stringify($oauth_verifier)             ;
        $this->authenticity_token    = UltimateOAuthModule::stringify($authenticity_token)         ;
        $this->cookie                = UltimateOAuthModule::arrayfy($cookie)                       ;
        $this->last_http_status_code = (int)UltimateOAuthModule::stringify($last_http_status_code) ;
        $this->last_called_endpoint  = UltimateOAuthModule::stringify($last_called_endpoint)       ;
    }
    
    /*
     *  (stdClass | array) get() - Wrapper for OAuthRequest.
     */
    public function get(
        $endpoint                , /*  Endpoint. Generally, it is returned as stdClass object.
                                    *    Example: "users/show"
                                    *  On some endpoints, it is returned as array if successful.
                                    *    Example: "statuses/home_timeline"
                                    */
        $params        = array()   // Parameters. Associative array or query string.
    ) {
        return $this->OAuthRequest($endpoint, 'GET', $params, true);
    }
    
    /*
     *  (stdClass | void) post() - Wrapper for OAuthRequest.
     */
    public function post(
        $endpoint                , /*  Endpoint. Generally, it is returned as stdClass object.
                                    *    Example: "statuses/update"
                                    */
        $params        = array() , // Parameters. Associative array or query string.
        $wait_response = true      // If you don't need to get a response, set it to FALSE.
    ) {
        return $this->OAuthRequest($endpoint, 'POST', $params, $wait_response);
    }
    
    /*
     *  (stdClass | array | void) OAuthRequest() - Used for requests mainly.
     */ 
    public function OAuthRequest(
        $endpoint                , /*  Endpoint. Generally, it is returned as stdClass object.
                                    *    Example: "users/show"
                                    *  On some endpoints, it is returned as array if successful.
                                    *    Example: "statuses/home_timeline"
                                    */
        $method        = 'GET'   , // Request type. Select "GET" or "POST".
        $params        = array() , // Parameters. Associative array or query string.
        $wait_response = true      // If you don't need to get a response, set it to FALSE.
    ) {
        // Validate parameters
        self::modParameters($params);
        return $this->request($endpoint, $method, $params, false, $wait_response, false);
    }
    
    /*
     *  (stdClass | void) OAuthRequestMultipart() - Used for multipart POST requests.
     */
    public function OAuthRequestMultipart(
        $endpoint                , /*  Endpoint. Generally, it is returned as stdClass object.
                                    *    Example: "statuses/update_with_media"
                                    */
        $params        = array() , // Parameters. Associative array.
        $wait_response = true      // If you don't need to get a response, set it to FALSE.
    ) {
        // Validate parameters
        self::modParameters($params);
        return $this->request($endpoint, 'POST', $params, true, $wait_response, false);
    }
    
    /*
     *  (string) getAuthorizeURL() - Return URL for authorization.
     */
    public function getAuthorizeURL($force_login = false) {
        return sprintf('%s://%s/oauth/authorize?oauth_token=%s%s',
            UltimateOAuthConfig::DEFAULT_SCHEME  ,
            UltimateOAuthConfig::DEFAULT_HOST    ,
            $this->request_token                 ,
            $force_login ? '&force_login=1' : ''  
        );
    }
    
    /*
     *  (string) getAuthenticateURL() - Return URL for authentication.
     */
    public function getAuthenticateURL($force_login = false) {
        return sprintf('%s://%s/oauth/authenticate?oauth_token=%s%s',
            UltimateOAuthConfig::DEFAULT_SCHEME  ,
            UltimateOAuthConfig::DEFAULT_HOST    ,
            $this->request_token                 ,
            $force_login ? '&force_login=1' : ''  
        );
    }
    
    /*
     *  (stdClass) directGetToken() - Used for para-xAuth authorization.
     *                                Return a stdClass object that has the following structure:
     *                                 
     *                                 (string) $response->oauth_token
     *                                 (string) $response->oauth_token_secret
     */
    public function directGetToken(
        $username, // screen_name or E-mail address.
        $password  // password.
    ) {
    
        try {
            
            // Validate arguments
            $username = UltimateOAuthModule::stringify($username);
            $password = UltimateOAuthModule::stringify($password);

            // Get request_token
            $res = $this->post('oauth/request_token');
            if (isset($res->errors)) {
                return UltimateOAuthModule::createErrorObject(
                    $res->errors[0]->message,
                    $this->last_http_status_code
                );
            }
                
            // Get authorize URL
            $url = $this->getAuthorizeURL(true);
            
            // Get authenticity_token
            $res = $this->request($url, 'GET', array(), false, true, true);
            $pattern = '@<input name="authenticity_token" type="hidden" value="([^"]++)" />@';
            if ($res === false) {
                return UltimateOAuthModule::createErrorObject(
                    'Connection failed when fetching authenticity_token.',
                    -1
                );
            }
            if (!preg_match($pattern, $res, $matches)) {
                return UltimateOAuthModule::createErrorObject(
                    'Failed to fetch authenticity_token.',
                    -1
                );
            }
            
            // Get oauth_verifier
            $params = array(
                'authenticity_token'         => $matches[1],
                'oauth_token'                => $this->request_token,
                'force_login'                => '1',
                'session[username_or_email]' => $username,
                'session[password]'          => $password,
            );
            $res = $this->request($url, 'POST', $params, false, true, true);
            if ($res === false) {
                return UltimateOAuthModule::createErrorObject(
                    'Connection failed when fetching oauth_verifier.',
                    -1
                );
            }
            $pattern = '@oauth_verifier=([^"]++)"|<code>([^<]++)</code>@';
            if (!preg_match($pattern, $res, $matches)) {
                return UltimateOAuthModule::createErrorObject(
                    'Wrong username or password.',
                    -1
                );
            }
            $this->oauth_verifier = !empty($matches[1]) ? $matches[1] : $matches[2];
            
            // Get access_token
            $res = $this->post('oauth/access_token', array(
                'oauth_verifier' => $this->oauth_verifier,
            ));
            if (isset($res->errors)) {
                return UltimateOAuthModule::createErrorObject(
                    $res->errors[0]->message,
                    $this->last_http_status_code
                );
            }
            
            // Return an object
            return $res;
        
        } catch (Exception $e) {
            
            // Return an error object
            return UltimateOAuthModule::createErrorObject(
                $e->getMessage(),
                $e->getCode()
            );
            
        }
    
    }
    
    /**************************/
    /**** Internal Methods ****/
    /**************************/
    
    /*
     *  (mixed) __get() - For read-only properties.
     */
    public function __get($name) {
        if (!isset($this->$name)) {
            throw new InvalidArgumentException("Undefined property: {$name}");
        }
        return $this->$name;
    }
    
    /*
     *  (void) modParameters() - Validate and modify parameters as appropriate formats.
     */
    private static function modParameters(&$params) {
        
        if (is_string($params)) {
            // Parse query string
            parse_str($params, $new);
        } elseif (is_object($params)) {
            // Convert object to array
            $new = (array)$params;
        } elseif (!is_array($params)) {
            // Invalid params
            $new = array();
        } else {
            $new = $params;
        }
        
        $ret = array();
        foreach ($new as $key => $value) {
            // Skip NULL
            if ($value === null) {
                continue;
            }
            // Convert FALSE to string "0"
            if ($value === false) {
                $value = '0';
            }
            // Stringification
            $ret[$key] = UltimateOAuthModule::stringify($value);
        }
        $params = $ret;
        
    }
    
    /*
     *  (string | void) connect() - Send socket request.
     */
    private function connect($host, $scheme, $request, $wait_response) {
        
        // Determine port
        if ($scheme === 'https') {
            $host = 'ssl://'.$host; // When using "https://"
            $port = 443;
        } else {
            $port = 80;
        }
        
        // Open socket
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$fp) {
            throw new RuntimeException($errstr);
        }
        
        // Send request
        if (@fwrite($fp, $request) === false) {
            if ($fp) {
                fclose($fp);
            }
            throw new RuntimeException('Failed to send request.');
        }
        
        // Get response
        if ($wait_response) {
            $res = explode("\r\n\r\n", stream_get_contents($fp), 2);
            if (!isset($res[1]) || !preg_match('@^HTTP/1\\.0 (\\d++)@', $res[0], $matches)) {
                throw new RuntimeException('Invalid response.');
            }
            $this->last_http_status_code = (int)$matches[1];
        }
        
        // Close socket
        fclose($fp);
        
        // Return void if response is not necessary
        if (!$wait_response) {
            return;
        }
            
        // Set cookies
        if (preg_match_all('/^Set-Cookie:(.+?)(?:;|$)/mi', $res[0], $matches)) {
            foreach ($matches[1] as $match) {
                $pair = explode('=', trim($match),2);
                if (!isset($pair[1])) {
                    continue;
                }
                $this->cookie[$pair[0]] = $pair[1];
            }
        }
        
        // Return response body
        return $res[1];
        
    }
    
    /*
     *  (stdClass | array | void) request() - HTTP request.
     */
    private function request($uri, $method, $params, $multipart, $wait_response, $scraping) {
        
        try {
            
            // Initialize information of last API call
            $this->last_http_status_code = -1;
            $this->last_called_endpoint  = '';
            
            // Validate arguments
            $uri    = UltimateOAuthModule::stringify($uri);
            $method = UltimateOAuthModule::stringify($method);
            $method = strtoupper($method);
            if ($multipart && ($method !== 'POST' || $scraping)) {
                throw new LogicException('Multipart requests are supported only on POST.');
            }
            
            // Parse uri
            $elements = UltimateOAuthModule::parse_uri($uri);
            
            // Combine parameters
            parse_str($elements['query'], $temp);
            $params += $temp;
            
            // Set oauth_verifier
            if (
                $elements['path'] === '/oauth/access_token' &&
                isset($params['oauth_verifier'])
            ) {
                $this->oauth_verifier = UltimateOAuthModule::stringify($params['oauth_verifier']);
                unset($params['oauth_verifier']);
            }
            
            if (!$scraping) {
                
                if (!$multipart) {
                    
                    $_params = array();
                    foreach ($params as $key => $value) {
                        if (strpos($key, '@') === 0) {
                            // Convert file names to file binaries
                            $value = UltimateOAuthModule::stringify($value);
                            if ($value !=='0' && !$value) {
                                throw new InvalidArgumentException("Filename is empty.");
                            }
                            if (!is_file($value)) {
                                throw new InvalidArgumentException("File \"{$value}\" not found.");
                            }
                            // Base64-encode binaries
                            $_params[substr($key,1)] = base64_encode(@file_get_contents($value));
                        } else {
                            $_params[$key] = $value;
                        }
                    }
                    $params = $_params;
                    unset($_params);
                    
                }
                
                // Get query string for OAuth authorization
                $query = $this->getQueryString(
                    $elements['scheme'].'://'.$elements['host'].$elements['path'],
                    $elements['path'],
                    $method,
                    $params,
                    $multipart
                );
                
            } else {
            
                $query = http_build_query($params, '', '&');
            
            }
            
            // Build path
            if ($method === 'GET' && !$multipart) {
                $path = $elements['path'].'?'.$query;
            } else {
                $path = $elements['path'];
            }
                
            // Build header lines
            $lines = array(
                sprintf('%s %s HTTP/1.0',strtoupper($method),$path),
                'Host: '       . $elements['host']               ,
                'User-Agent: ' . UltimateOAuthConfig::USER_AGENT ,
                'Connection: ' . 'Close'                         ,
                "\r\n"                                           ,
            );
            
            // Add cookies
            if ($this->cookie) {
                array_splice($lines, -1, 0, array(
                    'Cookie: '.implode('; ',UltimateOAuthModule::pairize($this->cookie)),
                ));
            }
            
            if ($multipart) {
            
                // Generate boundary
                $boundary = '--------------'.sha1($_SERVER['REQUEST_TIME']);
                
                // Build contents lines
                $cts_lines = array();
                foreach ($params as $key => $value) {
                    $cts_lines[] = '--'.$boundary;
                    // Convert file names to file binaries
                    if (strpos($key, '@') === 0) {
                        $value = UltimateOAuthModule::stringify($value);
                        if ($value !=='0' && !$value) {
                            throw new InvalidArgumentException("Filename is empty.");
                        }
                        if (!is_file($value)) {
                            throw new InvalidArgumentException("File \"{$value}\" not found.");
                        }
                        $is_file = true;
                        $disposition = sprintf('form-data; name="%s"; filename="%s"',
                            substr($key, 1),
                            md5(mt_rand())
                        );
                    } else {
                        $is_file = false;
                        $disposition = sprintf('form-data; name="%s"',
                            $key
                        );
                    }
                    array_push($cts_lines,
                        'Content-Disposition: ' .  $disposition              ,
                        'Content-Type: '        . 'application/octet-stream' ,
                        ''                                                   ,
                        $is_file ? @file_get_contents($value) : $value
                    );
                }
                $cts_lines[] = '--'.$boundary.'--';
                
                // Combine contents lines
                $contents = implode("\r\n",$cts_lines);
                
                // Add header lines
                $adds = array(
                    'Authorization: '  . 'OAuth '.$query                            ,
                    'Content-Type: '   . 'multipart/form-data; boundary='.$boundary ,
                    'Content-Length: ' . strlen($contents)                          ,
                );
                array_splice($lines, -1, 0, $adds);
                
            } elseif ($method === 'POST') {
                
                // Add header lines
                $adds = array(
                    'Content-Type: '   . 'application/x-www-form-urlencoded'        ,
                    'Content-Length: ' . strlen($query)                             ,
                );
                array_splice($lines, -1, 0, $adds);
                
            }
            
            // Combine header lines
            $request = implode("\r\n", $lines);
            
            if ($multipart) {
                // Add contents fields
                $request .= $contents;
            } elseif ($method === 'POST') {
                // Add query to the post fields
                $request .= $query;
            }
            
            // Connect
            $res = $this->connect(
                $elements['host']   ,
                $elements['scheme'] ,
                $request            ,
                $wait_response
            );
            
            // Update information of last API call
            $this->last_called_endpoint = $elements['path'];
            
            // Return void if response is not necessary
            if (!$wait_response) {
                return;
            }
            
            if ($scraping) {
            
                // Return HTML
                return $res;
                
            } elseif (
                !is_object($json = json_decode($res)) &&
                preg_match('@^/oauth/(?:(request)|access)_token$@', $elements['path'], $matches)
            ) {
                
                // Parse OAuth query string
                parse_str($res, $oauth_tokens);
                if (!isset($oauth_tokens['oauth_token'], $oauth_tokens['oauth_token_secret'])) {
                    throw new RuntimeException('Failed to parse response. There may be some errors.');
                }
                
                // Update properties
                if (empty($matches[1])) {
                    if (isset($oauth_tokens['oauth_token'])) {
                        $this->access_token        = $oauth_tokens['oauth_token'];
                    }
                    if (isset($oauth_tokens['oauth_token_secret'])) {
                        $this->access_token_secret = $oauth_tokens['oauth_token_secret'];
                    }
                    $res = (object)array(
                        'oauth_token'        => $this->access_token         ,
                        'oauth_token_secret' => $this->access_token_secret  ,
                    );
                } else {
                    if (isset($oauth_tokens['oauth_token'])) {
                        $this->request_token        = $oauth_tokens['oauth_token'];
                    }
                    if (isset($oauth_tokens['oauth_token_secret'])) {
                        $this->request_token_secret = $oauth_tokens['oauth_token_secret'];
                    }
                    $res = (object)array(
                        'oauth_token'        => $this->request_token        ,
                        'oauth_token_secret' => $this->request_token_secret ,
                    );
                }
                if (isset($oauth_tokens['user_id'], $oauth_tokens['screen_name'])) {
                    $res->user_id     = $oauth_tokens['user_id'];
                    $res->screen_name = $oauth_tokens['screen_name'];
                }
                
            } else {
                
                if (!is_object($json) && !is_array($json)) {
                    throw new RuntimeException('Failed to decode as JSON. There may be some errors on the request header.');
                }
                $res = $json;
                
            }
            
            // Modify deformed error response
            if (isset($res->error)) {
                $res = (object)array(
                    'errors' => array(
                        (object)array(
                            'code'    => -1          ,
                            'message' => $res->error ,
                        ),
                    ),
                );
            } elseif (isset($res->errors) && !is_array($res->errors)) {
                $res = (object)array(
                    'errors' => array(
                        (object)array(
                            'code'    => -1           ,
                            'message' => $res->errors ,
                        ),
                    ),
                );
            }
            
            // Override error codes with HTTP status code
            if (isset($res->errors)) {
                foreach ($res->errors as $error) {
                    $error->code = $this->last_http_status_code;
                }
            }
            
            // Return response
            return $res;
        
        } catch (Exception $e) {
            
            // Return an error object
            return UltimateOAuthModule::createErrorObject(
                $e->getMessage(),
                $this->last_http_status_code
            );
        
        }
    
    }
    
    /*
     *  (string) getQueryString() - Build query string for OAuth authorization.
     */
    private function getQueryString($uri, $path, $method, $opt, $as_header) {
        
        // Initialize parameters
        $parameters = array(
            'oauth_consumer_key'     => $this->consumer_key      ,
            'oauth_signature_method' => 'HMAC-SHA1'              ,
            'oauth_timestamp'        => $_SERVER['REQUEST_TIME'] ,
            'oauth_nonce'            => md5(mt_rand())           ,
            'oauth_version'          => '1.0'                    ,
        );
        
        // Add parameters
        if ($path === '/oauth/request_token') {
            $oauth_token_secret           = '';
        } elseif ($path === '/oauth/access_token') {
            $parameters['oauth_verifier'] = $this->oauth_verifier;
            $parameters['oauth_token']    = $this->request_token;
            $oauth_token_secret           = $this->request_token_secret;
        } else {
            $parameters['oauth_token']    = $this->access_token;
            $oauth_token_secret           = $this->access_token_secret;
        }
        if (!$as_header) {
            $parameters += $opt;
        }
        
        // Build body for signature
        $body = implode(
            '&',
            array_map(
                array(
                    'UltimateOAuthModule',
                    'enc',
                ),
                array(
                    $method,
                    $uri,
                    implode(
                        '&',
                        UltimateOAuthModule::pairize(
                            UltimateOAuthModule::nksort(
                                array_map(
                                    array(
                                        'UltimateOAuthModule',
                                        'enc',
                                    ),
                                    $parameters
                                )
                            )
                        )
                    ),
                )
            )
        );
        
        // Build key for signature
        $key = implode(
            '&',
            array_map(
                array(
                    'UltimateOAuthModule',
                    'enc',
                ),
                array(
                    $this->consumer_secret,
                    $oauth_token_secret,
                )
            )
        );
        
        // Build signature
        $parameters['oauth_signature'] = base64_encode(hash_hmac('sha1', $body, $key, true));
        
        // Return query string
        return implode(
            $as_header ?
                ', ' :
                '&'
            ,
            UltimateOAuthModule::pairize(
                array_map(
                    array(
                        'UltimateOAuthModule',
                        'enc',
                    ),
                    $parameters
                )
            )
        );
    
    }
    
}
 
 
 
 
/*  
 *  UltimateOAuthMulti - Multi request class.
 */ 
class UltimateOAuthMulti {
    
    /********************/
    /**** Properties ****/
    /********************/
    
    private $queues;
    private $filename;
    
    /***************************/
    /**** Interface Methods ****/
    /***************************/
    
    /*
     *  (UltimateOAuth) __construct() - Create a new UltimateOAuthMulti instance.
     */
    public function __construct() {
        // Initialize properties
        $this->queues   = array();
        $this->filename = str_replace('\\', '/', __FILE__);
    }
    
    /*
     *  (void) enqueue() - Enqueue a new request.
     */
    public function enqueue(
        UltimateOAuth &$uo, // arg1            : UltimateOAuth instance. Passed by reference.
        $method             // arg2            : Interface method name.
                            // arg3, arg4, ... : Arguments for method.
    ) {
        $this->queues[] = (object)array(
            'uo'       => &$uo,
            'method'   => UltimateOAuthModule::stringify($method),
            'args'     => array_slice(func_get_args(), 2),
        );
    }
    
    /*
     *  (array | void) execute() - Execute all requests.
     */
    public function execute(
        $wait_processes = true // If you don't need to get responses, set it to FALSE.
    ) {
    
        $ret = UltimateOAuthConfig::USE_PROC_OPEN ?
            $this->execute_by_proc_open($wait_processes) :
            $this->execute_by_fsockopen($wait_processes)
        ;
        
        // Clear queues
        $this->queues = array();
        
        return $ret;
        
    }
    
    /**************************/
    /**** Internal Methods ****/
    /**************************/
    
    /*
     *  (void) __sleep() - You can't serialize this object.
     */
    public function __sleep() {
        throw BadMethodCallException('This object is not serializable.');
    }
    
    /*
     *  (array | void) execute_by_proc_open()
     */
    private function execute_by_proc_open($wait_processes) {
    
        // Prepare proc_open() arguments
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $pipes = array();
        $procs = array();
        
        // Prepare PHP source
        $format = 
            '<?php '.PHP_EOL.
            'ob_start(); '.PHP_EOL.
            'require(\'%s\'); '.PHP_EOL.
            '$s = unserialize(\'%s\'); '.PHP_EOL.
            '$res = call_user_func_array(array($s->uo, $s->method), $s->args); '.PHP_EOL.
            '$res = serialize(array($s->uo, $res)); '.PHP_EOL.
            'ob_end_clean(); '.PHP_EOL.
            'echo $res; '.PHP_EOL.
            'exit();'
        ;
        
        // Open processes
        foreach ($this->queues as $i => $queue) {
            $procs[$i] = proc_open(
                UltimateOAuthConfig::PHP_COMMAND,
                $descriptorspec,
                $pipes[$i],
                null,
                null,
                array('bypass_shell' => true)
            );
            if (!$procs[$i]) {
                continue;
            }
            // Enable task to be executed parallelly
            stream_set_blocking($pipes[$i][0], 0);
            stream_set_blocking($pipes[$i][1], 0);
            stream_set_blocking($pipes[$i][2], 0);
            // Bind values
            $replace_pairs = array(
                '\\' => '\\\\' ,
                '\'' => '\\\'' ,
            );
            $text = sprintf($format,
                strtr($this->filename  , $replace_pairs),
                strtr(serialize($queue), $replace_pairs)
            );
            // Write PHP Source
            fwrite($pipes[$i][0], $text);
            fclose($pipes[$i][0]);
        }
        
        // Return void if response is not necessary
        if (!$wait_processes) {
            return;
        } elseif (!$this->queues) {
            return array();
        }
        
        // Get responses
        $res = array();
        foreach ($procs as $i => $proc) {
            // Opening failure
            if (!$proc) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Failed to start process.');
                continue;
            }
            // Wait response
            while (($status = proc_get_status($proc)) && $status['running']);
            // Get contents
            if (
                ($p1 = stream_get_contents($pipes[$i][1])) === false ||
                ($p2 = stream_get_contents($pipes[$i][2])) === false
            ) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Failed to get stream contents.');
            } elseif ($p2 !== '') {
                $res[$i] = UltimateOAuthModule::createErrorObject(strip_tags($p2));
            } elseif ($p1 === '' || ($r = @unserialize($p1)) === false) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Failed to get valid stream contents.');
            } else {
                $res[$i] = $r[1];
                $this->queues[$i]->uo = $r[0];
            }
            // Free resource
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            proc_close($proc);
        }
        
        // Return responses
        return $res;
        
    }
    
    /*
     *  (array | void) execute_by_fsockopen()
     */
    private function execute_by_fsockopen($wait_processes) {
        
        // Prepare URI elements
        $uri = parse_url(UltimateOAuthConfig::FULL_URL_TO_THIS_FILE);
        if (!$uri || !isset($uri['host'])) {
            $uri = false;
        } else {
            if (!isset($uri['path'])) {
                $uri['path'] = '/';
            }
            if (!isset($uri['port'])) {
                $uri['port'] = $uri['scheme'] === 'https' ? 443 : 80;
            }
        }
        
        // Open sockets
        $fps = array();
        $res = array();
        foreach ($this->queues as $i => $queue) {
            if ($uri === false) {
                $fps[$i] = false;
                continue;
            }
            $host = $uri['scheme'] === 'https' ? 'ssl://'.$uri['host'] : $uri['host'];
            $fps[$i] = @fsockopen($host, $uri['port'], $errno, $errstr, 5);
            if (!$fps[$i]) {
                continue;
            }
            stream_set_blocking($fps[$i], 0);
            stream_set_timeout($fps[$i], 60);
            $postfield = json_encode(array(
                'uo' => array(
                    'consumer_key'          => $queue->uo->consumer_key,
                    'consumer_secret'       => $queue->uo->consumer_secret,
                    'access_token'          => $queue->uo->access_token,
                    'access_token_secret'   => $queue->uo->access_token_secret,
                    'request_token'         => $queue->uo->request_token,
                    'request_token_secret'  => $queue->uo->request_token_secret,
                    'oauth_verifier'        => $queue->uo->oauth_verifier,
                    'authenticity_token'    => $queue->uo->authenticity_token,
                    'cookie'                => $queue->uo->cookie,
                    'last_http_status_code' => $queue->uo->last_http_status_code,
                    'last_called_endpoint'  => $queue->uo->last_called_endpoint,
                ),
                'method' => $queue->method,
                'args' => $queue->args,
            ));
            $postfield = http_build_query(array(
                UltimateOAuthConfig::MULTIPLE_REQUEST_KEY_NAME => $postfield,
            ), '', '&', PHP_QUERY_RFC3986);
            $length = strlen($postfield);
            $user_agent = UltimateOAuthConfig::USER_AGENT;
            $header = 
                "POST {$uri['path']} HTTP/1.0\r\n".
                "Host: {$uri['host']}\r\n".
                "User-Agent: {$user_agent}\r\n".
                "Connection: Close\r\n".
                "Content-Type: application/x-www-form-urlencoded\r\n".
                "Content-Length: {$length}\r\n".
                "\r\n".
                $postfield
            ;
            fwrite($fps[$i], $header);
            $res[$i] = '';
        }
        
        // Return void if response is not necessary
        if (!$wait_processes) {
            return;
        } elseif (!$this->queues) {
            return array();
        }
        
        // Get responses
        Do {
            $active = false;
            foreach ($fps as $i => $fp) {
                // Skip failed resourse
                if (!$fp) {
                    $res[$i] = false;
                    unset($fps[$i]);
                    continue;
                }
                // Skip failed result
                if (($tmp = stream_get_contents($fp)) === false) {
                    $res[$i] = null;
                    fclose($fp);
                    unset($fps[$i]);
                    continue;
                }
                $res[$i] .= $tmp;
                // Check EOF
                if (feof($fp)) {
                    fclose($fp);
                    unset($fps[$i]);
                    continue;
                }
                $active = true;
            }
        } while ($active);
        
        // Check responses
        foreach ($res as $i => $r) {
            // Invalid URI
            if ($uri === false) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Invalid URI.');
                continue;
            }
            // Socket opening failure
            if ($r === false) {
                $res[$i] = UltimateOAuthModule::createErrorObject($errstr);
                continue;
            }
            // Getting contents failure
            if ($r === null) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Failed to get stream contents.');
                continue;
            }
            // Empty string error
            if ($r === '') {
                $res[$i] = UltimateOAuthModule::createErrorObject('Request to this file itself may be blocked.');
                continue;
            }
            // Invalid response
            if (
                !($r = explode("\r\n\r\n", $r, 2))  ||
                !isset($r[1])                       ||
                !is_object($r = json_decode($r[1])) ||
                !isset($r->result)
            ) {
                $res[$i] = UltimateOAuthModule::createErrorObject('Failed to get valid stream contents.');
                continue;
            } 
            // Get result
            $res[$i] = $r->result;
            // Reconstruction
            if (isset(
                    $r->uo->consumer_key,
                    $r->uo->consumer_secret,
                    $r->uo->access_token,
                    $r->uo->access_token_secret,
                    $r->uo->request_token,
                    $r->uo->request_token_secret,
                    $r->uo->oauth_verifier,
                    $r->uo->authenticity_token,
                    $r->uo->cookie,
                    $r->uo->last_http_status_code,
                    $r->uo->last_called_endpoint
            )) {
                $this->queues[$i]->uo = new UltimateOAuth(
                    $r->uo->consumer_key,
                    $r->uo->consumer_secret,
                    $r->uo->access_token,
                    $r->uo->access_token_secret,
                    $r->uo->request_token,
                    $r->uo->request_token_secret,
                    $r->uo->oauth_verifier,
                    $r->uo->authenticity_token,
                    $r->uo->cookie,
                    $r->uo->last_http_status_code,
                    $r->uo->last_called_endpoint
                );
            }
        }
        
        // Return responses
        return $res;
        
    }
    
    /*
     *  (void) checkRequest - Output requested results.
     */
    public static function checkRequest() {
        
        // Check validity
        if (
            UltimateOAuthConfig::USE_PROC_OPEN ||
            !isset($_POST[UltimateOAuthConfig::MULTIPLE_REQUEST_KEY_NAME])
        ) {
            return;
        }
        
        // Check inputs
        if (
            !is_object($data = json_decode($_POST[UltimateOAuthConfig::MULTIPLE_REQUEST_KEY_NAME])) ||
            !isset(
                $data->uo->consumer_key,
                $data->uo->consumer_secret,
                $data->uo->access_token,
                $data->uo->access_token_secret,
                $data->uo->request_token,
                $data->uo->request_token_secret,
                $data->uo->oauth_verifier,
                $data->uo->authenticity_token,
                $data->uo->cookie,
                $data->uo->last_http_status_code,
                $data->uo->last_called_endpoint,
                $data->method,
                $data->args
            )
        ) {
            echo json_encode(array(
                'result' => UltimateOAuthModule::createErrorObject('Invalid POST data.')
            ));
            return;
        }
        
        // Prepare for calling
        $uo = new UltimateOAuth(
            $data->uo->consumer_key,
            $data->uo->consumer_secret,
            $data->uo->access_token,
            $data->uo->access_token_secret,
            $data->uo->request_token,
            $data->uo->request_token_secret,
            $data->uo->oauth_verifier,
            $data->uo->authenticity_token,
            $data->uo->cookie,
            $data->uo->last_http_status_code,
            $data->uo->last_called_endpoint
        );
        $method = UltimateOAuthModule::stringify($data->method);
        $args   = UltimateOAuthModule::arrayfy($data->args);
        
        // Check callability
        if (!is_callable(array($uo, $method))) {
            echo json_encode(array(
                'result' => UltimateOAuthModule::createErrorObject('Can\'t call "'.$method.'"')
            ));
            return;
        }
        
        // Prepare error handler
        set_error_handler(array('UltimateOAuthModule','errorHandler'), E_ALL);
        
        // Call
        ob_start();
        $res = call_user_func_array(array($uo, $method), $args);
        $error = ob_get_clean();
        if ($error !== '') {
            echo json_encode(array(
                'result' => UltimateOAuthModule::createErrorObject($error)
            ));
            return;
        }
        
        // Output result
        echo json_encode(array(
            'result' => $res,
            'uo'     => array(
                'consumer_key'          => $data->uo->consumer_key,
                'consumer_secret'       => $data->uo->consumer_secret,
                'access_token'          => $data->uo->access_token,
                'access_token_secret'   => $data->uo->access_token_secret,
                'request_token'         => $data->uo->request_token,
                'request_token_secret'  => $data->uo->request_token_secret,
                'oauth_verifier'        => $data->uo->oauth_verifier,
                'authenticity_token'    => $data->uo->authenticity_token,
                'cookie'                => $data->uo->cookie,
                'last_http_status_code' => $data->uo->last_http_status_code,
                'last_called_endpoint'  => $data->uo->last_called_endpoint,
            ),
        ));
        
        exit();
        
    }
    
}
 
 
 
 
/*  
 *  UltimateOAuthRotate - Rotation managing class.
 *                        This enables you to avoid API limits easily.
 */ 
class UltimateOAuthRotate {
    
    /********************/
    /**** Properties ****/
    /********************/
    
    private $current;
    private $instances;
    
    /***************************/
    /**** Interface Methods ****/
    /***************************/
    
    /*
     *  (UltimateOAuthRotate) __construct() - Create a new UltimateOAuthRotate instance.
     */
    public function __construct() {
        // Initialize properties
        $this->current = array(
            'POST' => null,
            'GET'  => array(),
        );
        $this->instances = array(
            'original' => array(),
            'official' => array(),
        );
        foreach (self::getOfficialKeys() as $name => $consumer) {
            $this->instances['official'][$name] = new UltimateOAuth(
                $consumer['consumer_key'],
                $consumer['consumer_secret']
            );
        }
    }
    
    /*
     *  (bool) setCurrent() - Select instance for POST request specified by name.
     */
    public function setCurrent(
        $name // The name you registered
    ) {
        foreach ($this->instances as $type => $keys) {
            foreach ($keys as $key => $instance) {
                if ($key === $name) {
                    $this->current['POST'] = array($type, $name);
                    return true;
                }
            }
        }
        return false;
    }
    
    /*
     *  (bool) resister() - Register your original consumer_key.
     */
    public function register(
        $name,           // Used for identification.
        $consumer_key,   // Consumer Key
        $consumer_secret // Consumer Secret
    ) {
        if (isset($this->instances['official'][$name])) {
            return false;
        }
        $this->instances['original'][$name] = new UltimateOAuth(
            $consumer_key,
            $consumer_secret
        );
        return true;
    }
    
    /*
     *  (bool | array) login() - Login.
     */
    public function login(
        $username,             // screen_name or E-mail address.
        $password,             // password.
        $return_array = false  // If you need each response, set it to TRUE.
    ) {
        
        // Create a new UltimateOAuthMulti instance
        $uom = new UltimateOAuthMulti;
        
        // Enqueue
        foreach ($this->instances as &$keys) {
            foreach ($keys as &$instance) {
                $uom->enqueue($instance, 'directGetToken', $username, $password);
            }
        }
        
        // Execute
        $res = array_combine(
            array_keys($this->instances['original'] + $this->instances['official']),
            $uom->execute()
        );
        
        // Return results
        if (!$return_array) {
            foreach ($res as $r) {
                if (isset($r->errors)) {
                    return false;
                }
            }
            return true;
        } else {
            return $res;
        }
        
    }
    
    /*
     *  (UltimateOAuth | bool) getInstance($name) - Return the clone of instance specified by name.
     */
    public function getInstance(
        $name // The name you registered
    ) {
        foreach ($this->instances as $keys) {
            foreach ($keys as $key => $instance) {
                return clone $instance;
            }
        }
        return false;
    }
    
    /**************************/
    /**** Internal Methods ****/
    /**************************/
    
    /*
     *  (mixed) __call() - You can call the methods in UltimateOAuth class.
     */
    public function __call(
        $name, // Method name.
        $args  // Arguments.
    ) {
        
        try {
            
            // These endpoints require official consumer_key
            $post_ex = array(
                '/friendships/accept.json',
                '/friendships/deny.json',
                '/friendships/accept_all.json',
            );
            
            if (
                !strcasecmp($name, 'get') ||
                !strcasecmp($name, 'OAuthRequest') && (
                    isset($args[1]) && !strcasecmp($args[1], 'GET') ||
                    count($args) < 2
                )
            ) {
                
                /*
                 *  GET request
                 */
                
                // First argument is necessary
                if (!isset($args[0])) {
                    throw new InvalidArgumentException('First argument is necessary.');
                }
                
                // Get endpoint
                $elements = UltimateOAuthModule::parse_uri($args[0]);
                $endpoint = $elements['path'];
                
                // Create table
                $table = array_keys(self::getOfficialKeys());
                
                // Count up
                if (!isset($this->current['GET'][$endpoint])) {
                    $this->current['GET'][$endpoint] = 0;
                } else {
                    $this->current['GET'][$endpoint]++;
                }
                
                // If the key doesn't exist, reset it to 0
                if (!isset($table[$this->current['GET'][$endpoint]])) {
                    $this->current['GET'][$endpoint] = 0;
                }
                
                // Select instance
                $obj = $this->instances['official'][$table[$this->current['GET'][$endpoint]]];
                
                // Return result
                return call_user_func_array(array($obj, $name), $args);
                
            } elseif (
                !strcasecmp($name, 'post') ||
                !strcasecmp($name, 'OAuthRequest') && isset($args[1]) && !strcasecmp($args[1], 'POST') ||
                !strcasecmp($name, 'OAuthRequestMultipart')
            ) {
                
                /*
                 *  POST request
                 */
                
                // Initialize if necessary
                if ($this->current['POST'] === null) {
                    if ($this->instances['original']) {
                        $this->current['POST'] = array('original', key($this->instances['original']));
                    } else {
                        $keys = array_keys($this->official);
                        $this->current['POST'] = array('official', key($this->instances['official']));
                    }
                }
                
                // Select instance
                list($app_type, $app_name) = $this->current['POST'];
                $obj = $this->{$app_type}[$app_name];
                
                // Judge if official consumer_key necessary
                foreach ($post_ex as $ex) {
                    if (strpos($args[0], $ex) !== false) {
                        $obj = reset($this->instances['official']);
                        break;
                    }
                }
                
                // Return result
                return call_user_func_array(array($obj, $name), $args);
                
            } else {
                
                throw new BadMethodCallException("Failed to call '{$name}'.");
            
            }
            
        } catch (Exception $e) {
            
            // Return an error object
            return UltimateOAuthModule::createErrorObject($e->getMessage());
        
        }
    
    }
    
    /*
     *  (array) getOfficialKeys() - Let's take advantage of leaked consumer_key 
     */
    private static function getOfficialKeys($include_signup = false) {
        $ret = array(
            'Twitter for iPhone' => array(
                'consumer_key'    => 'IQKbtAYlXLripLGPWd0HUA',
                'consumer_secret' => 'GgDYlkSvaPxGxC4X8liwpUoqKwwr3lCADbz8A7ADU',
            ),
            'Twitter for Android' => array(
                'consumer_key'    => '3nVuSoBZnx6U4vzUxf5w',
                'consumer_secret' => 'Bcs59EFbbsdF6Sl9Ng71smgStWEGwXXKSjYvPVt7qys',
            ),
            'Twitter for iPad' => array(
                'consumer_key'    => 'CjulERsDeqhhjSme66ECg',
                'consumer_secret' => 'IQWdVyqFxghAtURHGeGiWAsmCAGmdW3WmbEx6Hck',
            ),
            'Twitter for Mac' => array(
                'consumer_key'    => '3rJOl1ODzm9yZy63FACdg',
                'consumer_secret' => '5jPoQ5kQvMJFDYRNE8bQ4rHuds4xJqhvgNJM4awaE8',
            ),
            'Twitter for Windows Phone' => array(
                'consumer_key'    => 'yN3DUNVO0Me63IAQdhTfCA',
                'consumer_secret' => 'c768oTKdzAjIYCmpSNIdZbGaG0t6rOhSFQP0S5uC79g',
            ),
            'TweetDeck' => array(
                'consumer_key'    => 'yT577ApRtZw51q4NPMPPOQ',
                'consumer_secret' => '3neq3XqN5fO3obqwZoajavGFCUrC42ZfbrLXy5sCv8',
            ),
        );
        // This doesn't have permisson to access direct messages
        if ($include_signup) {
            $ret += array(
                'Twitter for Android Sign-Up' => array(
                    'consumer_key'    => 'RwYLhxGZpMqsWZENFVw',
                    'consumer_secret' => 'Jk80YVGqc7Iz1IDEjCI6x3ExMSBnGjzBAH6qHcWJlo',
                )
            );
        }
        return $ret;
    }

}
 
 
 
 
/*  
 *  UltimateOAuthModule - Module static method class. 
 */ 
class UltimateOAuthModule {
    
    /*
     *  (array) nksort() - Sort by natural order and return it
     */
    public static function nksort($arr) {
        uksort($arr, 'strnatcmp');
        return $arr;
    }
    
    /*
     *  (string) enc() - For helping PHP 5.2 bugs.
     */
    public static function enc($str) {
        return str_replace('%7E', '~', rawurlencode($str));
    }
    
    /*
     *  (array) pairize() - Combine keys and values with "=".
     */
    public static function pairize($arr) {
        $ret = array();
        foreach ($arr as $key => $value) {
            $ret[] = $key.'='.$value;
        }
        return $ret;
    }
    
    /*
     *  (string) stringify() - Safe casting to string.
     */
    public static function stringify($var) {
        return
            (
                !is_array($var) &&
                !is_resource($var) &&
                (!is_object($var) || method_exists($var, '__toString'))
            ) ? 
            (string) $var : 
            ''
        ;
    }
    
    /*
     *  (array) arrayfy() - Safe casting to 1D array.
     */
    public static function arrayfy($var) {
        $ret = array();
        if (is_array($var) || is_object($var)) {
            foreach ($var as $k => $v) {
                $ret[$k] = self::stringify($v);
            }
        }
        return $ret;
    }
    
    /*
     *  (stdClass) createErrorObject() - Return an error object.
     */
    public static function createErrorObject($msg, $code = -1) {
        return json_decode(
            sprintf('{"errors":[{"message":%s,"code":%d}]}',
                json_encode($msg),
                $code
            )
        );
    
    }
    
    /*
     *  (bool) errorHandler() - Output error in STDOUT.
     */
    public static function errorHandler($errno, $errstr, $errline, $errfile) {
        switch($errno) {
            case E_ERROR:
                $errno = 'Fatal error'; break;
            case E_WARNING:
                $errno = 'Warning'; break;
            case E_PARSE:
                $errno = 'Parse error'; break;
            case E_NOTICE:
                $errno = 'Notice'; break;
            default:
                $errno = 'Unknown error';
        }
        printf('PHP %s:  %s in %s on line %d'.PHP_EOL,
            $errno, $errstr, $errfile, $errline
        );
        return true;
    }
    
    /*
     *  (array) parse_uri() - A wrapper of parse_url().
     */
    public static function parse_uri($uri) {
        $uri = self::stringify($uri);
        if ($uri === '' || ($elements = parse_url($uri)) === false) {
            throw new InvalidArgumentException('Invalid URI.');
        }
        if (!isset($elements['host'])) {
            $elements['host']   = UltimateOAuthConfig::DEFAULT_HOST;
            $elements['scheme'] = UltimateOAuthConfig::DEFAULT_SCHEME;
            $elements['path']   = preg_replace('@^/++@', '', $elements['path']);
            if (
                strpos($elements['path'], '1/')   !== 0 &&
                strpos($elements['path'], '1.1/') !== 0 &&
                strpos($elements['path'], 'i/')   !== 0
            ) {
                if (
                    strpos($elements['path'], 'activity/')  === 0 ||
                    strpos($elements['path'], '/activity/') !== false
                ) {
                    $elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_ACTIVITY_API_VERSION.'/'.$elements['path'];
                } elseif (
                    strpos($elements['path'], 'oauth/')  !== 0 &&
                    strpos($elements['path'], 'oauth2/') !== 0
                ) {
                    $elements['path'] = '/'.UltimateOAuthConfig::DEFAULT_API_VERSION.'/'.$elements['path'];
                } else {
                    $elements['path'] = '/'.$elements['path'];
                    $is_oauth = true;
                }
            } else {
                $elements['path'] = '/'.$elements['path'];
            }
            if (!isset($is_oauth) && !preg_match('@/[^/]*+\\.[^/.]++$@', $elements['path'])) {
                $elements['path'] .= '.json';
            }
        } elseif (!isset($elements['path'])) {
            $elements['path'] = '/';
        }
        if (strpos($elements['path'], 'oauth2/') === 0) {
            throw new Exception('This library doesn\'t support OAuth 2.');
        }
        if (!isset($elements['query'])) {
            $elements['query']  = '';
        }
        return $elements;
    }

}
 
 
 
 
/*
 *  Check request to this file itself.
 */
UltimateOAuthMulti::checkRequest();
