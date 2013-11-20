<?php
//
//  Stupeflix video generation web services client library
//  All functions will throw exception in case of error
//  See examples for usage, and http://wiki.stupeflix.com for more information.
//

// This is the client class to access the stupeflix services
class Stupeflix extends StupeflixBase 
{
    const TEXT_XML_CONTENT_TYPE = "text/xml";
    const APPLICATION_ZIP_CONTENT_TYPE = "application/zip";
    const APPLICATION_URLENCODED_CONTENT_TYPE = "application/x-www-form-urlencoded";
    public static $__debug = false;  // Debug flag
    private static $parametersToAdd;
    private $base_url;   // Stupeflix Service

    // Class constructor
    // @param accessKey : User access Key
    // @param secretKey : User secret Key
    // @param service   : Name of the service
    // @param debug     : Debug mode or not
    public function __construct($accessKey, $secretKey, $host = "http://services.stupeflix.com", $service = 'stupeflix-1.0', $debug = false)
    {
        if ($host[strlen($host) - 1] == "/") {
            $host = substr($host, 0, strlen($host) - 1);
        }
                  
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->base_url  = $host . '/' . $service;
        $this->service = $service;
        $this->debug = $debug;
        // Currently there is only the Marker parameter (used for partial enumeration)
        self::$parametersToAdd = array(StupeflixBase::MARKER_PARAMETER, StupeflixBase::MAXKEYS_PARAMETER);
    }

    // Create a new connection object
    // @return : A new connection object
    public function connectionGet()
    {
        $connection = new StupeflixConnection($this->base_url, $this->debug);
        return $connection;
    }

    // Build the url for access to the definition of a user/resource
    // @param user     : The user
    // @param resource : The resource
    // @return         : The url for the request
    private function definitionUrl($user, $resource)
    {
      return "/$user/$resource/definition/";
    }

    // Send a definition to the service
    // The file to be sent can be a zip file containing a xml description file movie.xml and other assets, or simply movie.xml.
    // In the first case, all images should be in "images" zip sub-directory, music under "music" sub-directory etc.
    // In the latter case, all assets reference should be urls (images, music ...)
    // See wiki.stupeflix.com for more information
    // @param user     : The user name
    // @param resource : The resource name
    // @param filename : Name of the file to be uploaded
    // @return         : True in case of success, will throw an exception otherwise
    public function sendDefinition($user, $resource, $filename, $body = null)
    {
        // Check parameters
        if(! $user || ! $resource || ! ($filename || $body))
        {
            throw new Exception("Stupeflix sendDefinition : user, resource and (filename or body) must be defined.");
        }
        // Build the request url
        $url = $this->definitionUrl($user, $resource);

        // Set the content type according to upload type
        if (self::isZip($filename, $body)) {
            $contentType = self::APPLICATION_ZIP_CONTENT_TYPE;
        } else {
            $contentType = self::TEXT_XML_CONTENT_TYPE;
        }
        // Finally send the content through a HTTP PUT
        $this->sendContent("PUT", $url, $filename, $body, $contentType);
        // Always return True : errors are throwed through exceptions
        return True;
    }

    // Get a definition from the service
    // @param user     : The user name
    // @param resource : The resource name
    // @param filename : Name of the file where data will be downloaded
    // @return         : True in case of success, will throw an exception otherwise
    public function getDefinition($user, $resource, $filename)
    {
        // Check parameters
        if(! $user || ! $resource || ! $filename)
        {
            throw new Exception("Stupeflix getDefinition : user, resource and filename must be defined.");
        }
        // Build the request url
        $url = $this->definitionUrl($user, $resource);
        // Get the content
        $this->getContent($url, $filename);
        // Always return True : errors are thrown through exceptions
        return True;
    }

    // Build the base url to access the video generated from user/resource with the given profile
    // @param user     : The user name
    // @param resource : The resource name
    // @param profile  : The profile name
    // @return         : The base url
    private function profileUrl($user, $resource, $profile)
    {
        return "/$user/$resource/$profile/";
    }

    // Build the signed url to access a video generated from user/resource with the given profile
    // @param user           : The user name
    // @param resource       : The resource name
    // @param profile        : The profile name
    // @param followredirect : if True, a totally resolved url will be given, with no redirect (the function follows the link manually, doing network accesses)
    // @return               : The signed url
    public function getProfileURL($user, $resource, $profile, $followredirect = true)
    {
        // Check parameters
        if(!$user || !$resource || !$profile)
        {
            throw new Exception("Stupeflix getProfileURL: all parameters should be defined");
        }

        // Build base url
        $url = $this->profileUrl($user, $resource, $profile);
        // Sign the url
        $url = $this->signUrl($url, "GET", "", "");
        // Add the base url
        $url = $this->base_url . $url;

        // If a direct url is needed, manually follow the redirect (will do a network access, of course)        
        if($followredirect) 
        {
            $url = $this->__getRedirectTarget($url);
        }
        return $url;
    }

    // Build the signed url to access a video generated from user/resource with the given profile
    // @param user     : The user name
    // @param resource : The resource name
    // @param profile  : The profile name
    // @return         : True on success, otherwise an exception will be raised
    public function getProfile($user, $resource, $profile, $filename)
    {
        // Check parameters
        if(!$user || !$resource || !$profile || !$filename)
        {
            throw new Exception("Stupeflix getProfile: all parameters should be defined");
        }
        // Get the profile url
        $url = $this->profileUrl($user, $resource, $profile);
        // Retrieve the content from the profiel url
        $this->getContent($url, $filename);
        return true;
    }


    // Build the base url to launch the generation of a set of profiles
    // @param user     : The user name
    // @param resource : The resource name
    // @return         : The base url
    private function createProfilesUrl($user, $resource)
    {
        return "/$user/$resource/";
    }

    // Launch the generation of a set of profiles for a given user/resource
    // @param user     : The user name
    // @param resource : The resource name
    // @param profiles : A StupeflixProfileSet object (recommended) or an array of string 
    //                     containing the names of the profiles to be generated (deprected).
    // @return         : true upon success, otherwise a exception will be raised
    public function createProfiles($user, $resource, $profiles)
    {
        // Create the base url
        $url = $this->createProfilesUrl($user, $resource);
        $body = "";
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        if (is_array($profiles))
        {
            $profiles = StupeflixProfileSet::deflt($profiles);
        }
        
        $xml .= $profiles->xmlGet();
        $body = self::XML_PARAMETER . "=" . urlencode($xml);
        $this->sendContent("POST", $url, null, $body, self::APPLICATION_URLENCODED_CONTENT_TYPE);
        return true;
    }

    // Build url for profile status querying.
    // The status can be asked on every user, user alone, user/resource or
    // user/resource/profile.
    // @param user     : The user name
    // @param resource : The resource name
    // @param profile  : The profile name
    // @return         : The built url
    private function profileStatusUrl($user = null, $resource = null, $profile = null, $marker = null, $maxKeys = null)
    {
        // Build an array with the different parts
        $a = array($user, $resource, $profile);
        $str = "/";
        // Enumerate the parts
        foreach ($a as $e)
        {
            if ($e)
            {
                $str .= "$e/";
            }
            else
            {
                // as soon as there is a null part, stops
                break;
            }
        }
        $str .= "status/";
        if ($marker != null || $maxKeys != null) {
            $str .= "?";
        }
        if ($marker != null) {
            if (! is_array($marker) || count($marker) != 3) {
                throw new Exception("Invalid marker: should be a 3 lengthed array: array('user', 'resource', 'profile')");
            }            
            $str .= MARKER_PARAMETER . "=" . implode("%2F", $marker);
        }
        if ($maxKeys != null) {
            $str .= MAXKEYS_PARAMETER_PARAMETER . "=" . $maxKeys;
        }
        return $str;
    }    

    // Get Profile status
    // The status can be asked on every user, user alone, user/resource or
    // user/resource/profile.
    // The returned object is an array of dictionaries, one for each matching profiles.
    // Each dictionary contains the user, resource and profiles names, and the status for this specific profile.
    // The status itself is a dictionary with following keys
    //      - status   : always present : general status : queued, generating, available, or error
    //      - complete : appear after generating has started : gives the percentage done for profile
    //      - error    : if status is error : give the error string
    // @param user     : The user name
    // @param resource : The resource name
    // @param profile  : The profile name
    // @param marker   : If not null, must be an array [user0, resource0, profile0]. 
    //                   Then, every returned status will be for keys where user/resource/profile is strictly greater than user0/resource0/profile0 in alphabetical order.
    // @return         : A php object giving the status for all matching profiles, will throw a exception upon error.
    public function getProfileStatus($user = null, $resource = null, $profile = nulll, $marker = null, $maxKeys = null)
    {
        $url = $this->profileStatusUrl($user, $resource, $profile, $marker, $maxKeys);
        $connection = $this->getContent($url, null);
        $body = $connection->response->body;
        $ret = json_decode($body);
        if ($ret === null || $ret === false)
        {
            throw new Exception("Stupeflix::getProfileStatus: could not decode from json: " . $body);
        }
        return $ret;
    }
    
    private function profileThumbUrl($user, $resource, $profile, $thumbUrl)
    {
        return "/$user/$resource/$profile/$thumbUrl/";
    }

    public function getProfileThumbURL($user, $resource, $profile)
    {
        $url = $this->profileThumbURL($user, $resource, $profile, "thumb.jpg");
        // Sign the url
        $url = $this->signUrl($url, "GET", "", "");
        // Add the base url
        $url = $this->base_url . $url;
        $url = $this->__getRedirectTarget($url);        
        return $url;
    }

    public function getProfileThumb($user, $resource, $profile)
    {
        $url = $this->getProfileThumbURL($user, $resource, $profile);
        $this->getContent($url, $filename);
    }

    // Build the base url to launch the generation of a set of profiles
    // @param user     : The user name
    // @param resource : The resource name
    // @return         : The base url
    private function profilePreviewUrl($user, $resource, $profile)
    {
        return "/$user/$resource/$profile/preview.flv/";
    }

    public function getProfilePreviewUrl($user, $resource, $profile)
    {
        // Build base url
        $url = $this->profilePreviewUrl($user, $resource, $profile);
        // Sign the url
        $url = $this->signUrl($url, "GET", "", "", null, true);
        // Add the base url
        $url = $this->base_url . $url;
        return $url;
    }

    // Get the marker to recall getProfileStatus once more.
    // @param profileStatusSet : the set of status returned by a previous call to getProfileStatus
    public function getMarker($profileStatusSet) {
        $lastStatus = $profileStatusSet[-1];
        return array($lastStatus["user"], $lastStatus["resource"], $lastStatus["profile"]);
    }

    // Helper function to report error.
    // Always throws an exception.
    // @param connection : The connection that was in error
    // @param message    : Context of the error
    public function answer_error($connection, $message)
    {
        $body = $connection->response->body;
        throw new Exception("$message,\nERROR: $body");
    }

    // Helper function to send some content.
    // @param method      : HTTP method, "PUT" or "POST"
    // @param url         : The base url to be used (will be signed by this function)
    // @param filename    : Optional filename containing the body data to be sent
    // @param body        : Optional string containing the body data to be sent (one of body or filename must be defined)
    // @param contentType : Content type to be used in headers
    // @return            : true on success, will throw an exception on error
    public function sendContent($method, $url, $filename, $body, $contentType)
    {
        // Check parameters
        if(!$url)
        {
            throw new Exception("Stupeflix sendContent: url should be defined");
        }

        if(($filename && $body) || ((! $filename) &&  (!$body)))
        {
            throw new Exception("Stupeflix sendContent: exactly one of filename and body should be defined");
        }

        if ($body)
        {
            // If body is defined, hash the body
            $md5hashes = $this->md5string($body);
            $size = strlen($body);
        }
        else
        {
            // Otherwise, hash the file
            $md5hashes = $this->md5file($filename);
            $size = filesize($filename);
        }

        // Get the right version of md5 : base64 one
        $md5base64 = $md5hashes['base64'];
        // Build the headers array
        $headers = array(self::HEADER_CONTENT_MD5    => $md5base64,
                         self::HEADER_CONTENT_LENGTH => $size,
                         self::HEADER_CONTENT_TYPE   => $contentType);

        // Sign the url
        $url = $this->signUrl($url, $method, $md5base64, $contentType);

        // Execute the request
        $connection = $this->connectionGet();
        $connection->execute($method, $url, $filename, $body, $headers);

        // Check the return status
        $status = $connection->response->code;
        if ($status != 200)
        {
            $this->answer_error($connection, "Stupeflix::sendContent: bad status: $status");
        }

        // Check the returned etag (hex form of md5): should be the same as the sent one
        $refEtag = $md5hashes['hex'];
        $etag = $connection->response->headers['etag'];

        if ($etag != $refEtag)
        {
            $this->answer_error($connection, "Stupeflix::sendContent: bad etag $etag != $refEtag (ref)");
        }
        // Always return true, an Exception is raised otherwise before that.
        return true;
    }

    // Retrieve content from an url. Content will be put in a file or returned.
    // @param url      : The url to be queried
    // @param filename : The optional file where to put the data
    // @return         : The StupeflixConnection that was used to send the request
    public function getContent($url, $filename)
    {
        // Check parameters
        if(!$url)
        {
            throw new Exception("Stupeflix getContent: url should be defined");
        }

        // Method is always "GET"
        $method = "GET";
        // Sign the url
        $url = $this->signUrl($url, $method, "", "");

        // Send the request
        $connection = $this->connectionGet();
        $connection->execute("GET", $url, $filename, null, null);

        // Check the status code
        $status = $connection->response->code;
        if ($status != 200)
        {
            $this->answer_error($connection, "Stupeflix::getContent: bad status: $status");
        }

        // Get the returned etag
        $etag = $connection->response->headers['etag'];

        // Hash the result (from file or body)
        if ($filename)
        {
            $md5hashes = $this->md5file($filename);
        }
        else
        {
            $md5hashes = $this->md5string($connection->response->body);
        }

        // Get the right version of md5 (hex one)
        $refEtag = $md5hashes['hex'];

        // Check that etag matches.
        if ($etag != $refEtag) {
            $this->answer_error($connection, "Stupeflix::getContent: bad etag $etag != $refEtag (ref)");
        }
        // Return the underlying StupeflixConnection object
        return $connection;
    }
    
    // Manually follow a redirect (single redirect)
    private function __getRedirectTarget($url) {
        // Initialize the curl library
        $ch = curl_init();
        // set options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Execute curl
        $data = curl_exec($ch);
        // Retrieve the return node
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        // close curl
        @curl_close($ch);         
        // test if it was a redirect
        if ($http_code == 301 || $http_code == 302 || $http_code == 307) 
        {
            // Yes it was
            $matches = array();
            preg_match('/Location:(.*?)\n/', $data, $matches);
            // retrieve the location
            $url = trim($matches[1]);
        }
        // return the redirect target
        return $url;
    }

}

// Helper class for url signing : base class for Stupeflix and StupeflixStorage classes
class StupeflixBase 
{
    const HEADER_CONTENT_TYPE = "Content-Type";
    const HEADER_CONTENT_LENGTH = "Content-Length";
    const HEADER_CONTENT_MD5 = "Content-MD5";
    const ACCESS_KEY_PARAMETER="AccessKey";
    const SIGNATURE_PARAMETER="Signature";
    const DATE_PARAMETER="Date";
    const PROFILE_PARAMETER = "Profiles";
    const XML_PARAMETER = "ProfilesXML";
    const MAXKEYS_PARAMETER = "MaxKeys";
    const MARKER_PARAMETER = "Marker";

    // Utility function for debugging
    public function logdebug($s)
    {
        if ($this->debug)
        {
            echo "$s\n";
        }
    }

    // Build the canonical string containing all parameters for signing
    // @param parameters : The additional parameters to be added to the string to sign
    // @return : The canonical string for parameters
    public static function paramString($parameters)
    {
        $paramStr = "";
        // Check if there are really some parameters
        if ($parameters)
        {
            //  Enumerate the fixed list of parameters that should be added to string to sign
            foreach (self::$parametersToAdd as $p)
            {
                // Check presence of parameter in given parameters array
                if(array_key_exists($p, $parameters))
                {
                    // If the parameter is present, add it to the string to sign
                    $v = $parameters[$p];
                    $paramStr .= "$p\n$v\n";
                }
            }
        }
        // Return the canonical parameter string
        return $paramStr;
    }

    // Build the string to sign for a query
    // @param method     : HTTP Method (should be "GET", "PUT" or "POST" )
    // @param url        : base url to be signed
    // @param md5        : content md5
    // @param mime       : mime type ("" for GET query)
    // @param datestr    : date  (seconds since epoch)
    // @param parameters : additional url parameters
    // @return : The string to sign
    public function strToSign($method, $url, $md5, $mime, $datestr, $parameters)
    {
        // Build the canonical parameter string
        $paramStr = self::paramString($parameters);
        // Build the full service path
        $path = '/' . $this->service . $url;
        // Build the full string to be signed
        $stringToSign  = "$method\n$md5\n$mime\n$datestr\n$path\n$paramStr";
        if ($this->debug) {
            $this->logdebug("String to Sign : $stringToSign");
        }
        return $stringToSign;
    }

    // Sign a request
    // @param string     : The String to be signed
    // @param secretKey  : The secretKey to be user
    // @return : The hmac signature for the request
    public static function sign($string, $secretKey)
    {
        return hash_hmac('sha1', $string, $secretKey, false);
    }

    // Sign an request, using url, method body ...
    // @param url        : The url to be signed
    // @param method     : The HTTP method to be used for request
    // @param md5        : The md5 of the body, or "" for "GET" requests
    // @param mime       : The mime type of the request
    // @param parameters : Some optional additional parameters
    // @return the hmac signature of the request
    public function signUrl($url, $method, $md5, $mime, $parameters = null, $inlineAuth = false)
    {
        // Get seconds since epoch, integer type
        $now = floor(time());
        // Build the string to be signed
        $strToSign = self::strToSign($method, $url, $md5, $mime, $now, $parameters);
        // Build the signature
        $signature = self::sign($strToSign, $this->secretKey);
        // Build the signed url
        $accessKey = $this->accessKey;
        $accessKeyParam = self::ACCESS_KEY_PARAMETER;
        $dateParam = self::DATE_PARAMETER;
        $signParam = self::SIGNATURE_PARAMETER;
        // Add date, public accesskey, and signature to the url
        if ($inlineAuth) {
            $url .= "$accessKey/$signature/$now/";
        } else {
            $url .= "?$dateParam=$now&$accessKeyParam=$accessKey&$signParam=$signature";
        }
        // Finally add, if needed, additional parameters
        if ($parameters != null)
        {
            foreach ($parameters as $k => $v)
            {
                $url .= "&$k=$v";
            }
        }
        return $url;
    }

    // Build a (md5, md5 hexadecimal, md5 base 64) array for a given md5
    // @return : The array
    public static function md5triplet($md5)
    {
        $md5hex = bin2hex ($md5);
        $md5base64 = base64_encode($md5);
        return array('std'=>$md5, 'hex'=>$md5hex, 'base64'=>$md5base64);
    }

    // Compute the (md5, md5 hexadecimal, md5 base 64) triplet for of a file
    // @param filename : The file to be hashed
    // @return         : The md5 array
    public static function md5file($filename)
    {
        if (!file_exists($filename) || !is_file($filename) || !is_readable($filename))
        {
            throw new Exception("Stupeflix::inputFile: Unable to open input file: $filename");
        }

        $md5 = md5_file($filename, true);
        return self::md5triplet($md5);
    }

    // Compute the (md5, md5 hexadecimal, md5 base 64) triplet for of a string
    // @param str : The string to be hashed
    // @return    : The MD5 array
    public static function md5string($str)
    {
        $md5 = md5($str, true);
        return self::md5triplet($md5);
    }

    // Check if a filename is a zip
    // @param filename : The file name
    // @return         : A boolean : true if it is a zip, false otherwise
    public static function isZip($filename, $string = null)
    {
        if ($filename) 
        {
            // Open the file name
            $f = fopen($filename, "r");
            // read 4 bytes
            $data = fread($f, 4);
            // close the file
            fclose($f);
        } else {
            $data = substr($string, 0, 4);
        }
        // Check the header
        return ($data == "PK\03\04");
    }    
}


// Helper class to build HTTP connections, using curl php bindings
final class StupeflixConnection {

    // Buid a new connection
    public function __construct($baseurl, $debug)
    {
        $this->baseurl = $baseurl;
        $this->debug = $debug;
    }

    // Execute the http request
    // @param method     : The HTTP method, "GET", "PUT" or "POST"
    // @param url        : The url to be used
    // @param filename   : The optional filename to be sent of filled, depending on method
    // @param body       : The optional body to be sent of filled, depending on method
    // @param headers    : Optional headers to be added
    public function execute($method, $url, $filename, $body, $headers) 
    {
        // Unpack headers and build a curl compatible version
        $hds = array();
        if ($headers)
        {
            foreach ($headers as $header => $value) 
            {
                // Content length will be added by curl, so remove it
                if ($header == 'Content-Length') 
                {
                    continue;
                }
                if (strlen($value) > 0) {
                    $hds[] = $header . ': ' . $value;
                }
            }
        }
        
        // Init a curl connection
        $curl = curl_init();
        // Init reponse body
        $this->response->body = "";

        $this->fh = null;
        // Switch on request type
        switch ($method) 
        {
            case 'GET':
                // If filename is specified, open the file for write 
                if ($filename)
                {
                    $this->fh = fopen($filename, 'w');
                }
                // Use a callback to write data chunk by chunk
                curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
                break;
            case 'PUT':
              if ($filename) 
              {
                  // Open the input file for reading
                  $fh = fopen($filename, 'r');
                  $filesize = filesize($filename);
                  // Setup curl : method is put, file to read, and file size
                  curl_setopt($curl, CURLOPT_PUT, 1);
                  curl_setopt($curl, CURLOPT_INFILE, $fh);
                  curl_setopt($curl, CURLOPT_INFILESIZE, $filesize);                
              }
              else
              {
                  $fh = null;
                  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');  
                  curl_setopt($curl, CURLOPT_POSTFIELDS, $body);  
              }
              
              // Use a callback to get response 
              curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
              break;
            case 'POST':
                // Setup curl : method is post
                curl_setopt($curl, CURLOPT_POST, 1);
                // Body is given as post fields.
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_WRITEFUNCTION, array(&$this, '__responseWriteCallback'));
                break;
        }
        // Set common curl parameters
        $fullurl = $this->baseurl . $url;
        curl_setopt($curl, CURLOPT_URL, $fullurl);
#        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
#        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        // Always follow redirection, as the api is built largely using 307 redirects.
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        // Set the headers
        curl_setopt($curl, CURLOPT_HTTPHEADER, $hds);
        // Ignore returned header using standard way ...
        curl_setopt($curl, CURLOPT_HEADER, false);
        // ... but get them by a call back function
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, array(&$this, '__responseHeaderCallback'));

        // In debug mode, let curl write those http cryptic messages...
        if ($this->debug)
        {
            curl_setopt($curl, CURLOPT_VERBOSE, true);
        }

        // Finally, execute the query
        $curlOutput = curl_exec($curl);

        // Execute, grab errors
        if ($curlOutput) 
        {
            // Everything went fine
            @curl_close($curl);
        }
        else
        {
            // Something went bad ... build response error object
            $this->response->error = array('code' => curl_errno($curl),
                                           'message' => curl_error($curl),
                                           'resource' => $this->resource);

            @curl_close($curl);
        }
        // In case of PUT method, we had an opened file to close
        switch ($method) 
        {
            case 'PUT':
              if ($fh)
              {
                  fclose($fh);
              }
        }
    }

    /**
     * CURL write callback
     *
     * @param curl CURL resource
     * @param data  Data just received
     * @return integer
     */
    private function __responseWriteCallback(&$curl, &$data) {
        if ($this->response->code == 200 && $this->fh !== null) 
	{
            return fwrite($this->fh, $data);
        }
        else
        {
            $this->response->body .= $data;
            return strlen($data);
        }
    }

    /**
     * CURL header callback
     *
     * @param resource &$curl CURL resource
     * @param string &$data Data
     * @return integer
     */
    private function __responseHeaderCallback(&$curl, &$data) {
        if (($strlen = strlen($data)) <= 2) return $strlen;
        if (substr($data, 0, 4) == 'HTTP')
          $this->response->code = (int)substr($data, 9, 3);
        else {
            list($header, $value) = explode(': ', trim($data), 2);
            if ($header == 'Last-Modified')
              $this->response->headers['time'] = strtotime($value);
            elseif ($header == StupeflixBase::HEADER_CONTENT_LENGTH)
              $this->response->headers['size'] = (int)$value;
            elseif ($header == StupeflixBase::HEADER_CONTENT_TYPE)
              $this->response->headers['type'] = $value;
            elseif ($header == 'ETag') {
                if ($value[0] == '"') {
                    $value = substr($value, 1, -1);
                }
                $this->response->headers['etag'] = $value;
            }
        }
        return $strlen;
    }
}

////////////////////////////////////////////////////////////////////////////////
//
// Utility classes to build profile creation XML.
// This XML contains notification and upload settings.
// The node hierarchy is (? denote optional, + at least one node):
// (Each class name is of course prefixed by Stupeflix)
//
//  ProfileSet
//    Meta ?
//    Notification ?
//    Profile +  
//      Meta ?
//      Notification ?
//      Upload +
//        Meta ?
//
//  
// Notification is done using HTTP POST with an application/x-www-form-urlencoded 
//   content type.
//
// See http://wiki.stupeflix.com for more information on notification parameters,
//   and upload.
// 
// More upload types to come soon.
// 
////////////////////////////////////////////////////////////////////////////////

// Top level xml class : set of profiles to be generated
class StupeflixProfileSet extends StupeflixXMLNode {
    // Build a set, using a set of StupeflixProfile ($profiles), and optional top level meta set
    public function __construct($profiles, $meta = null, $notify = null)
    {   
        // Build the full set of children
        $children = $this->metaChildrenAppend($meta, $notify, $profiles);
        // Call the super constructor
        parent::__construct("profiles", null, $children, null);
    }  
 
    // Build an array containing all the profile names, enumerating children
    public function getProfileNames()
    {
        // Build the array to be returned
        $ret = array();
        // Enumerate children
        foreach ($this->children as $c) 
        {
            // Test if this is a meta or a profile
            if ($c->nodeName == "profile") 
            {
                // This is really a profile
                $ret[] = $c->attributes["name"];
            }
        }         
        return $ret;
    }

    // Static function to build a default profile when only profile names are given, without a full xml definition for children    
    public static function deflt($profiles) {
        // Set of profiles to be built
        $profSet = array();
        foreach ($profiles as $p)
        {
            // Create the default upload directive
            $upload = new StupeflixDefaultUpload();
            // Create a profile, with the correct name and a single upload, the default one, and add it to the array
            $profSet[] = new StupeflixProfile($p, array($upload));
        }
        // Return a set of profiles
        return new StupeflixProfileSet($profSet);
    }
}
class StupeflixDictNode extends StupeflixXMLNode {
    // Build an instance: $dict is key/value dictionary
    public function __construct($name, $dict)
    {    
        // Initialize the children array
        $children = array();
        // Enumerate the dictionary
        foreach ($dict as $k => $v)
        {
            // Append new children : tag name is $k, inner text is $v
            $children[] = new StupeflixXMLNode($k, null, null, $v);
        }
        // Call the parent constructor, tag name is meta, children set is "children"
        parent::__construct($name, null, $children, null);
    }   
}

// Class for handling meta : simple set of xml encoded key/value pairs
// Meta can be used in StupeflixProfileSet, StupeflixProfile, or StupeflixUpload
// Children meta override parent ones : for example, 
// if the same key is specified in StupeflixProfileSet and in a StupeflixUpload, the StupeflixUpload value is taken.
class StupeflixMeta extends StupeflixDictNode {
    // Build an instance: $dict is key/value dictionary
    public function __construct($dict)
    {    
        parent::__construct("meta", $dict);
    }   
}

// Class for profiles
class StupeflixProfile extends StupeflixXMLNode {
    public function __construct($profileName, $uploads, $meta = null, $notify = null)
    {
        // Append children and meta to build the full children set
        $children = $this->metaChildrenAppend($meta, $notify, $uploads);
        // Call the super constructor : profile tag, name attribute is $profileName, with children
        parent::__construct("profile", array("name"=> $profileName), $children, null);
    }   
}

// StupeflixNotify : upload the video using a POST to the given url, using multipart/form-data 
class StupeflixNotify extends StupeflixXMLNode {
    // Create the upload object
    public function __construct($url, $statusRegexp = null)
    {
        $attributes = array("url"=>$url);
        if ($statusRegexp != null) {
            $attributes["statusRegexp"] = $statusRegexp;
        }
          
        parent::__construct("notify", $attributes, null);
    }   
}

// Base class for upload
class StupeflixUpload extends StupeflixXMLNode {
    public function __construct($name, $parameters, $meta = null)
    {
        $children = $this->metaChildrenAppend($meta, null, null);
        parent::__construct($name, $parameters, $children, null);
    }   
}

// StupeflixHttpPOSTUpload : upload the video using a POST to the given url, using multipart/form-data 
class StupeflixHttpPOSTUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($url, $meta = null)
    {
        parent::__construct("httpPOST", array("url"=>$url), $meta);
    }   
}

// StupeflixHttpPUTUpload : upload the video using a PUT to the given url, using multipart/form-data 
class StupeflixHttpPUTUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($url, $meta = null)
    {
        parent::__construct("httpPUT", array("url"=>$url), $meta);
    }   
}

// StupeflixYoutubeUpload : upload the video to youtube
class StupeflixYoutubeUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($login, $password, $meta = null)
    {
        parent::__construct("youtube", array("login" => $login, "password" => $password), $meta);
    }   
}

// StupeflixBrightcoveUpload : upload the video to brightcove
class StupeflixBrightcoveUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($token, $id = null, $meta = null)
    {
        if ($id != null) {
            $parameters = array("sid"=>$token, "reference_id"=>$id);
        } else {
            $parameters = array("sid"=>$token);
        }

        parent::__construct("brightcove", $parameters, $meta);
    }   
}

// StupeflixYoutubeTokenUpload : upload the video to youtube
class StupeflixYoutubeTokenUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($developerKey, $token, $meta = null)
    {
        parent::__construct("youtube", array("developerkey" => $developerKey, "sid" => $token), $meta);
    }   
}

// StupeflixFacebookTokenUpload : upload the video to youtube
class StupeflixFacebookTokenUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($apiKey, $secret, $token, $meta = null)
    {
        parent::__construct("facebook", array("apikey" => $apiKey, "secret" => $secret, "sid" => $token), $meta);
    }   
}

// StupeflixYoutubeUpload : upload the video to youtube
class StupeflixDailymotionUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($login, $password, $sid = null, $meta = null)
    {
        if ($sid != null) {
            $parameters = array("sid"=>$sid);
        } else {
            $parameters = array("login" => $login, "password" => $password);
        }
        parent::__construct("dailymotion", $parameters, $meta);
    }   
}

// StupeflixFTP Upload : upload the video to youtube
class StupeflixFTPUpload extends StupeflixUpload {
    // Create the upload object
    public function __construct($server, $login, $password, $directory, $meta = null)
    {
        $parameters = array("server" => $server, "login" => $login, "password" => $password, "directory" => $directory);
        parent::__construct("ftp", $parameters, $meta);
    }   
}

// StupeflixS3Upload : upload the video to an arbitrary Amazon S3 bucket
class StupeflixS3Upload extends StupeflixUpload {
    // Create the upload object
    public function __construct($bucket, $accesskey = null, $secretkey = null, $prefix = null,  $meta = null)
    {
        $params = array("bucket" => $bucket);
        if ($accesskey != null) {
            $params["accesskey"] = $accesskey;
        }
        if ($secretkey != null) {
            $params["secretkey"] = $secretkey;
        }
        if ($prefix != null) {
            $params["resourcePrefix"] = $prefix;
        }

        parent::__construct("s3", $params, $meta);
    }   
}


// StupeflixDefaultUpload : use the stupeflix temporary storage to store the video
class StupeflixDefaultUpload extends StupeflixUpload {
    public function __construct($meta = null)
    {
        parent::__construct("stupeflixStore", array(), $meta);
    }   
}

// Base class for xml nodes
class StupeflixXMLNode {
    // Build a node with given xml attributes, children nodes, and inner text
    public function __construct($nodeName, $attributes = null, $children = null, $text = null)
    {
        $this->children = $children;
        $this->attributes = $attributes;
        $this->nodeName = $nodeName;
        $this->text = $text;
    } 
    // Build the xml string recursively
    public function xmlGet() 
    {
        // Start with the opening tag
        $docXML = '<' . $this->nodeName;
        // Add the attributes
        if ($this->attributes && count($this->attributes) != 0) 
        {
            foreach ($this->attributes as $k => $v)               
            { 
                $docXML .= " ";
                // Escape the attribute values 
                $docXML .= $k . '="'. $this->xmlentities($v) . '"';
            }
        }
        // End of the opening tag
        $docXML .= '>';
        // Enumerate children if needed
        if($this->children)
        {
            foreach ($this->children as $c)
            {
                // Retrieve the children xml string
                $docXML .= $c->xmlGet();
            }
        }
        // Add the inner text if needed
        if ($this->text)
        {
            $docXML .= $this->xmlentities($this->text);
        }
        // Build the closing tag
        $docXML .= '</' . $this->nodeName. '>';
        // return the xml
        return $docXML;
    }
    // Utility function to append meta and other children, testing nullity if needed
    public function metaChildrenAppend($meta, $notify, $children0) 
    {
        // Build the overall array
        $children = array();
        // Check if appending the meta is needed
        if ($meta)
        {
            $children[] = $meta;
        }
        // Check if appending the meta is needed
        if ($notify)
        {
            $children[] = $notify;
        }
        // Check if appending the other children is needed
        if ($children0)
        {
            $children = array_merge($children, $children0);
        }
        // Return the full array of children
        return $children;
    }
    
    public function xmlentities ($string){
        return str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&apos;', '&lt;', '&gt;'), $string);
    }
}

class StupeflixDefinition {
    /**
     * You have the responsability to create a proper temporary filename, and to destroy the file after the definition has been sent
     **/
    public function __construct($filename)
    {
        $this->zip = new ZipArchive();
        if ($this->zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE)
        {
            throw new Exception("StupeflixDefinition constructor: could not create zip archive in $filename");
        }
        $this->definitionFileSet = false;
    }

    function setDefinitionFile($filename)
    {
        $localname = 'movie.xml';
        if ($this->definitionFileSet)
        {
            throw new Exception('Definition File already set.');
        }
        if (! $this->zip->addFile($filename, $localname))
        {
            throw new Exception("StupeflixDefinition setDefinitionFile: could not add $localname file to archive.");
        }
        $this->definitionFileSet = true;
    }

    function addMedia($name, $kind, $filename) {
        $localname = "$kind/$name";
        if (! $this->zip->addFile($filename, $localname))
        {
            throw new Exception("StupeflixDefinition addImage: could not add $kind $filename file to archive using name $localname.");
        }
    }

    function addImage($name, $filename)
    {
        $this->addMedia($name, "images", $filename);
    }

    function addVideo($name, $filename)
    {
        $this->addMedia($name, "videos", $filename);
    }

    function finalize()
    {
        if (!$this->definitionFileSet) {
            throw new Exception("StupeflixDefinition flush: not definition file set before flushing.");
        }
        $this->zip->close();
    }

}

?>