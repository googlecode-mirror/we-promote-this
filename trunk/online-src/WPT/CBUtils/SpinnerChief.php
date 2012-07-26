<?php

/*
 * Class: SpinnerChief
 * Date: 6/24/2011
 * Author: Christopher D. Queen - http://www.ChrisQueen.com
 * Description: This is a php wrapper class that provides functionality for using the SpinnerChief server
 * For more info visit http://spinnerchief.com
 */

require_once 'Zend/Loader.php';
// the Zend dir must be in your include_path (Get Zend From http://www.zend.com/en/community/downloads)
Zend_Loader::loadClass('Zend_Http_Client');

class SpinnerChief {
    public $SpinnerUrl;
    // The url to connect to the SpinnerChief Api Server

    /*
     * Function __construct
     *
     *                  Construct the Spinner Url with the api key, username and password. (The port number is optional)
     *
     * Parameters
     *
     * $apiKey:
     *                  This is the apiKey provided for developers. For using Spinnerchief API as developer, you need to register a developer
     *                  account at http://developer.spinnerchief.com and then you will get an API Key after logging into your developer account
     *
     * $userName:
     *                  This is the username for the user using your program. When a user wants to use your program with the Spinnerchief API,
     *                  the user needs to register a Spinnerchief account at http://account.spinnerchief.com and then fill in his account
     *                  username
     *
     * $password:
     *                  This is the password for the user using the program.
     *
     * $port:
     * (optional)
     *                  This is the port number to connect to the SpinnerChief api. Currently you can only use 9001, 8000, 8080 or 443
     */
    function __construct($apiKey, $userName, $password, $port = 9001) {
        $this -> SpinnerUrl = "http://api.spinnerchief.com:" . $port . "/apikey=" . $apiKey . "&username=" . $userName . "&password=" . $password;
    }

    /*
     * Function spinArticle
     *
     *                  This function will spin your article using the parameters provided or return an error message from the server
     *
     * Parameters
     * $article:
     *                  This is the article content you wish to spin using SpinnerChief
     *
     *
     * $spintype:       Default - 0
     * (optional)
     *                  When $spintype=0, SpinnerChief will return the spun article in {} (Spyntax) format. For example, if your article is
     *                  "This is a great software", the return will be "{It|This} {is|must be} a {good|nice} {software|program}".
     *                  When $spintype=1, SpinnerChief will return the spun article directly. For example, if your article is
     *                  "This is a great software", the return will be "It is a nice program".
     *
     * $spinFreq:       Default - 4
     * (optional)
     *                  The $spinFreq means word spin frequency, for example if $spinFreq=1, every word will be spun, if $spinFreq=3, 1/3 of all
     *                  words will be spun, etc.
     *
     * $autoSpin:       Default - 1
     * (optional)
     *                  When $autoSpin=0, SpinnerChief will not spin the words in your article without the {}(Spyntax) format.
     *                  For example, if you post an article like "{It|This} is a good software", SpinnerChief will only spin the {It|This} part,
     *                  other words will not get spun.
     *                  When $autoSpin=1, SpinnerChief will auto-spin the words in your article without the {}(Spyntax) format.
     *                  For example, if you post an article like "{It|This} is a good software", SpinnerChief will not only spin {It|This} part,
     *                  but also spin the words "good" and "software", so the return would be "This is a great program".
     *
     * $original:       Default - 0
     * (optional)
     *                  When $original=0, server will keep the original words in the spun article.
     *                  When $original=1, the server will delete the original word in the return result.
     *
     * $wordsCount:     Default - 5
     * (optional)
     *                  $wordsCount means how many words to use when spintype=0.
     *                  For example, if the article is "hello", $wordsCount=3, the result will be {hello|hi|hey}.
     *                  If $wordsCount=2, the result will be {hello|hi}.
     *
     * $protectHtml:    Default - 0
     * (optional)
     *                  When $protectHtml=0, the server will not spin the words in the html tags in your article.
     *                  When $protectHtml=1, the server will still spin the words in html tags in your article.
     *
     * $spinHtml:       Default - 0
     * (optional)
     *                  When $spinHtml=0, the server will still spin the {} part within html tags in your article.
     *                  When $spinHtml=1, the server will not spin the {} part within html tags in your article.
     *
     * $wordQuality:    Default - 0
     * (optional)
     *                  $wordQuality=0, use Best Thesaurus to spin
     *                  $wordQuality=1, use Better Thesaurus to spin
     *                  $wordQuality=2, use Good Thesaurus to spin
     *                  $wordQuality=3, use All Thesaurus to spin
     *                  $wordQuality=9, use Everyone?s favorite to spin
     *
     * $orderly:        Default - 0
     * (optional)
     *                  When $orderly=0, the server uses the thesaurus in its listed order to spin.
     *                  When $orderly=1, the server uses the thesaurus randomly to spin.
     *
     * $protectwords:   Default - null
     * (optional)
     *                  When you set $protectwords, the server will not spin words in the the protect words list,
     *                  the format is $protectwords=word1,word2,word3,phrase1,phrase2
     *
     * $queryTimes:     Default - null
     * (optional)
     *                  When $queryTimes=1, the server returns today's used query times of this account.
     *                  When $queryTimes=2, the server returns today's remaining query times of this account.
     *
     * $useHurricane:   Default - 1
     * (optional)
     *                  When $useHurricane=0, use hurricane.
     *                  When $useHurricane=1, don't use hurricane function.
     *                  If you don?t know what is this, please visit http://www.contenthurricane.com
     *
     * $charType:       Default - 1
     * (optional)
     *                  $charType=1, normal Chars.
     *                  $charType=2, special Chars.
     *                  $charType=3, non Unicode.
     *                  (This parameter will only work when $useHurricane=0.)
     *
     * $convertBase:    Default - 0
     * (optional)
     *                  $convertBase=0, convert based on char.
     *                  $convertBase=1, convert based on word.
     *                  (This parameter will only work when $useHurricane=0.)
     *
     * $oneCharForward: Default - 0
     * (optional)
     *                  $oneCharForward=0, don't use function "Only Convert One Char for every word".
     *                  $oneCharForward=1, use functon "Only Convert One Char for every word".
     *                  (This parameter will only work when $useHurricane=0.)
     *
     * $percent:        Default - 0
     * (optional)
     *                  percent=50, 50% Conversion Rate (Must be integer from 1 to 100.)
     *                  (This parameter will only work when $useHurricane=0.)
     *
     */
    function spinArticle($article, $spinType = 0, $spinFreq = 4, $autoSpin = 1, $original = 0, $wordsCount = 5, $protectHtml = 0, $spinHtml = 0, $wordQuality = 0, $orderly = 0, $protectwords = null, $queryTimes = null, $useHurricane = 1, $charType = 1, $convertBase = 0, $oneCharForward = 0, $percent = 0) {
        $parameters = array("spintype" => $spinType, "spinfreq" => $spinFreq, "autospin" => $autoSpin, "original" => $original, "Wordscount" => $wordsCount, "protecthtml" => $protectHtml, "spinhtml" => $spinHtml, "wordquality" => $wordQuality, "Orderly" => $orderly, "usehurricane" => $useHurricane, "Chartype" => $charType, "convertbase" => $convertBase, "onecharforword" => $oneCharForward, "percent" => $percent);
        if (isset($protectwords)) {
            if (is_array($protectwords)) {
                $protectwords = explode(",", $protectwords);
            }
            $parameters['protectwords'] = $protectwords;
        }
        if (isset($queryTimes)) {
            $parameters['querytimes'] = $queryTimes;
        }
        try {
            $url = $this -> SpinnerUrl;
            foreach ($parameters as $index => $value) {
                $url .= "&$index=$value";
            }
            $client = new Zend_Http_Client($url, array('maxredirects' => 5, 'timeout' => 30, 'keepalive' => true));
            $response = $client -> setRawData($article, 'text/plain') -> request('POST');
            $article = $response -> getBody();
        } catch ( Exception $e ) {
            $article = "Error: " . $e -> getMessage() . "\n
";
        }
        return $article;
    }

}
?>