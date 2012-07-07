<?php

error_reporting(E_ALL);

require_once 'AccountCreator.php';
require_once '../CBUtils/DeCaptcha.php';
require_once '../CBUtils/Name.php';

require_once 'Zend/Loader.php';
// the Zend dir must be in your include_path
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');

class YoutubeAccount extends AccountCreator {

    public $createdAccount;

    function constructClass() {

    }

    function getLoginUrl() {
        return "http://www.youtube.com/signup";
    }

    function create($username, $password) {
        if ($this -> hasValidHttpClient() && $this -> hasValidService()) {
            //echo("Has Valid HttpClient<br>");
            //var_dump($this -> httpClient);
            //echo("<br><br>");
            $person = new Name();
            //echo("Has name: " . $person -> firstName . " " . $person -> lastName . "<br>");

            try {
                $this -> createdAccount = $this -> service -> createUser($username, $person -> firstName, $person -> lastName, $password);

                $agreed = $this -> createdAccount -> getLogin() -> getAgreedToTerms();
                if (!$agreed) {
                    //Set password so we dont have to pass it
                    $login = $this -> createdAccount -> getLogin();
                    $login -> setPassword($password);

                    $this -> acceptTOSForUser($this -> createdAccount);
                    $this -> createdAccount = $this -> service -> retrieveUser($username);
                    $login = $this -> createdAccount -> getLogin();
                    echo("After Accepteing TOS for USER:<br>" . $login -> __toString() . "<br><br>");
                    var_dump($this -> createdAccount);
                    echo("<br><br>");
                }
            } catch (CaptchaRequiredException $e) {
                $src = $e -> getCaptchaUrl();
                echo("Please visit $src<br>");
                $deCaptcha = new DeCaptcha('frostbyte07', 'Neeuq011$');
                $captchaText = $deCaptcha -> getCatchaText($src);
                $this -> service -> setUserCredentials($username . "@wepromotethis.com", $password, $e -> getCaptchaToken(), $captchaText);
            } catch (AuthenticationException $e) {
                echo("Authentication exception: " . $e -> getMessage());
            } catch (Zend_Gdata_Gapps_ServiceException $e) {
                // Set the user to null if not found
                if ($e -> hasError(Zend_Gdata_Gapps_Error::ENTITY_DOES_NOT_EXIST)) {
                    $this -> createdAccount = null;
                } else {
                    // Outherwise, just print the errors that occured and exit
                    foreach ($e->getErrors() as $error) {
                        echo "Error encountered: " . $error -> getReason() . " (" . $error -> getErrorCode() . ")<br>";
                    }
                }
            } catch(Exception $e) {
                echo($e -> getMessage());
            }
        }
    }

    function acceptTOSForUser($user) {
        $login = $user -> getLogin();
        $userName = $login -> getUsername();
        $password = $login -> getPassword();

        echo("Gathered Username: $userName | Password: $password<br><br>");

        $accepted = false;
        if ($this -> hasValidHttpClient()) {
            //$loginURL = "https://accounts.google.com/ServiceLoginAuth?continue=http%3A%2F%2Fwww.youtube.com%2Fsignin%3Faction_handle_signin%3Dtrue%26feature%3Dupload%26nomobiletemp%3D1%26hl%3Den_US%26next%3Dhttp%253A%252F%252Fwww.youtube.com%252Fmy_videos_upload&service=youtube&uilel=3&dsh=-6813335703759222396&ltmpl=sso&hl=en_US";
            //$this -> httpClient ->setCookieJar ();
            //$this -> httpClient -> setUri($loginURL);
            //$this -> httpClient -> setHeaders(array('Accept-Encoding: gzip, deflate', 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0) Gecko/20100101 Firefox/4.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-us,en;q=0.5', 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'Keep-Alive: 115', 'Connection: keep-alive', 'Referer: http://www.youtube.com', 'Host: www.youtube.com', 'Cookie: __utma=173272373.199096328.1305393518.1305393518.1305525473.2; __utmz=173272373.1305393518.1.1.utmcsr=mail.google.com|utmccn=(referral)|utmcmd=referral|utmcct=/mail/u/0/; __utmc=173272373; GoogleAccountsLocale_session=en;'));

            $this -> httpClient = $this -> getYoutubeHttpClient($userName."@wepromotethis.com", $password);
            $clientResponse = $this -> httpClient -> request(Zend_Http_Client::POST);
            $response = $clientResponse -> getBody();

            echo("Response 1:<br>");
            var_dump($response);
            echo("<br><br><br>");

            $doc = new DOMDocument();
            $doc -> loadHTML($response);
            $xpath = new DOMXPath($doc);

            // get Form action
            foreach ($xpath->query ( '//form[contains(@id,"gaia_loginform")]' ) as $node) {
                $action = $node -> getAttribute("action");
            }

            echo("Form submit = $action<br><br>");

            //$parameters = array('Birthday' => $fullBirthday, 'Email' => $email, 'Passwd' => $password, 'PasswdAgain' => $password, 'newaccountcaptcha' => $captchaText, 'nshk' => 1, 'signup' => 'Create my new account!', 'smhck' => 1);
            $parameters = array("Email" => $userName, "Passwd" => $password, "signIn" => "Sign in", "service" => "youtube");

            //get all hidden inputs in form and add to parameters
            foreach ($xpath->query ( '//input[contains(@type,"hidden")]' ) as $node) {
                $parameters[$node -> getAttribute("name")] = $node -> getAttribute("value");
            }

            echo("Parameters:<br>");
            var_dump($parameters);
            echo("<br><br>");

            $this -> httpClient -> setUri($action);
            $this -> httpClient -> setParameterPost($parameters);
            $clientResponse = $this -> httpClient -> request(Zend_Http_Client::POST);
            $response = $clientResponse -> getBody();
            echo("Response 2:<br>");
            var_dump($response);
            echo("<br><br><br>");

        }
        return $accepted;
    }

    function getYoutubeHttpClient($userEmail, $password, $tries = 2) {
        $authenticationURL = Zend_Gdata_YouTube::CLIENTLOGIN_URL;
        $service = Zend_Gdata_YouTube::AUTH_SERVICE_NAME;
        $service = "gmail";
        if (isset($userEmail) && isset($password)) {
            try {
                $httpClient = Zend_Gdata_ClientLogin::getHttpClient($userEmail, $password, $service, null, 'wepromotethis.com', null, null, $authenticationURL,'GOOGLE');
            } catch(Exception $e) {
                echo("Error while getting Youtube httpclient: " . $e -> getMessage() . "<br><br>");
            }
        } else {
            //echo ("Credentials missing. Username: $userEmail | Password length: " . strlen ( $password ) . " <br>");
        }
        if (!isset($httpClient) && $tries > 0) {
            sleep(10);
            $httpClient = $this -> getYoutubeHttpClient($userEmail, $password, --$tries);
        }
        return $httpClient;
    }

    function getCreatedAccount() {
        return $this -> createdAccount;
    }

    function create_old($username, $password, $email) {
        $created = false;

        if ($this -> hasValidHttpClient()) {
            $this -> httpClient -> setHeaders(array('Accept-Encoding: gzip, deflate', 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0) Gecko/20100101 Firefox/4.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-us,en;q=0.5', 'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'Keep-Alive: 115', 'Connection: keep-alive', 'Referer: http://www.youtube.com', 'Host: www.youtube.com', 'Cookie: __utma=173272373.199096328.1305393518.1305393518.1305525473.2; __utmz=173272373.1305393518.1.1.utmcsr=mail.google.com|utmccn=(referral)|utmcmd=referral|utmcct=/mail/u/0/; __utmc=173272373; GoogleAccountsLocale_session=en;'));
            $clientResponse = $this -> httpClient -> request(Zend_Http_Client::POST);
            $response = $clientResponse -> getBody();

            echo("Response -1:<br>");
            var_dump($response);
            echo("<br><br><br>");

            $birthday = rand(1, 28);
            $birthmonth = rand(1, 12);
            $today = getdate();
            $cyear = $today['year'];
            $year = rand($cyear - 60, $cyear - 18);
            $fullBirthday = "$birthmonth/$birthday/$year";
            $gender = array('m', 'f');
            $gender = $gender[array_rand($gender)];
            $postal = array('201061', '21113', '28262', '23669', '23668', '22312');
            $postal = $postal[array_rand($postal)];
            echo("Date: $fullBirthday<br>Gender: $gender<br>Postal: $postal<br><br><br>");
            $this -> httpClient -> setUri('http://www.youtube.com/create_account');
            $this -> httpClient -> setParameterPost(array('action_save_user_info' => 'true', 'birthday_day' => $birthday, 'birthday_mon' => $birthmonth, 'birthday_yr' => $year, 'country' => 'US', 'current_form' => 'signupForm', 'email' => $email, 'find_me_via_email' => 'agreed', 'gender' => $gender, 'postal_code' => $postal, 'username' => $username));
            $clientResponse = $this -> httpClient -> request(Zend_Http_Client::POST);
            $response = $clientResponse -> getBody();

            echo("Response 1:<br>");
            var_dump($response);
            echo("<br><br><br>");

            // Solve Captcha
            $doc = new DOMDocument();
            $doc -> loadHTML($response);
            $xpath = new DOMXPath($doc);
            foreach ($xpath->query ( '//img[contains(@alt,"Visual verification")]' ) as $node) {
                $src = $node -> getAttribute("src");
            }

            $deCaptcha = new DeCaptcha('frostbyte07', 'Neeuq011$');
            $captchaText = $deCaptcha -> getCatchaText($src);

            echo("Captcha Image: <img src='$src'><br>Text: $captchaText<br><br>");

            // get Form action
            foreach ($xpath->query ( '//form[contains(@name,"createaccount")]' ) as $node) {
                $action = $node -> getAttribute("action");
            }

            $parameters = array('Birthday' => $fullBirthday, 'Email' => $email, 'Passwd' => $password, 'PasswdAgain' => $password, 'newaccountcaptcha' => $captchaText, 'nshk' => 1, 'signup' => 'Create my new account!', 'smhck' => 1);

            //get all hidden inputs in form and add to parameters
            foreach ($xpath->query ( '//input[contains(@type,"hidden")]' ) as $node) {
                $parameters[$node -> getAttribute("name")] = $node -> getAttribute("value");
            }

            echo("Paramets:<br>");
            print_r($parameters);
            echo("<br><br><br>");

            //die ();

            //$this->httpClient->setUri ( "https://www.google.com/accounts/CreateAccount?followup=http%3A%2F%2Fwww.youtube.com%2Ffinish_ssu&uilel=0&service=youtube&skipll=true&passive=false&skipvpage=true&hl=en-US&nui=17&ltmpl=ssu" );
            //$this->httpClient->setUri ( "https://www.google.com/accounts/CreateAccount" );
            $this -> httpClient -> setUri(urlencode($action));
            $this -> httpClient -> setParameterPost($parameters);
            $clientResponse = $this -> httpClient -> request(Zend_Http_Client::POST);
            $response = $clientResponse -> getBody();
            echo("Response 2:<br>");
            var_dump($response);
            echo("<br><br><br>");

            if (false) {
                //Failed
                $deCaptcha -> reportLastCatchaIncorrect();
            } else
                $created = true;

            /*
             $doc = new DOMDocument ( );
             $doc->loadHTML ( $response );
             $xpath = new DOMXPath ( $doc );
             foreach ( $xpath->query ( '//span[contains(@id,"screen-name")]/span[contans(@class,"content")]' ) as $node ) {
             $value = $node->textContent;
             // If success
             if (stripos ( $value, $username ) !== false) {
             $this->userName = $username;
             $this->email = $email;
             $this->password = $password;
             $this->firstName = $name;
             $created = true;
             break;
             }
             }
             */

        } else {
            echo("No Valid Http Client");
        }
        return $created;
    }

    function isEmailConfirmNeeded() {
        return true;
    }

}

$obj = new YoutubeAccount();
$username = "yttestwpt" . rand(1000, 4000);
$password = 'Neeuq011$';
$obj -> create($username, $password);
$user = $obj -> getCreatedAccount();
if (isset($user)) {
    echo("Created Users ($username) Password: $password <br>");
    var_dump($user);
    echo("<br>");
}
?>