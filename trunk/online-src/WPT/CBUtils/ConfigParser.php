<?php
/**
 * Author: Christopher D. Queen
 * Created: Jan 11, 2009
 * Description: ConfigParser.php locates the configuration file on the server and gives access to
 * the variables within the datasearch_config.xml and their values
 */
//require_once 'LogHelper.php';
/*!
 * Class: ConfigParser
 * This class parses the config file on the running server and creates an ConfigParser object
 * that holds the values of the fields defined in the Includes/configuration.xml file
 */
class ConfigParser
{
    public $fileName;
    /*?
	 * Function: readConfigFile
	 * Opens and XML file represented by $filename
	 * It will return an array of all field and value pairs in the xml file
	 */
    function readConfigFile ()
    {
        // read the XML database config file
        $data = implode("", file($this->fileName));
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $data, $values, $tags);
        xml_parser_free($parser);
        // loop through the structures in the file
        foreach ($tags as $key => $val) {
            if ($key == "ConfigurationConstants") {
                $valranges = $val;
                // each contiguous pair of array entries are the
                // lower and upper range for each aim constant definition
                for ($i = 0; $i < count($valranges); $i += 2) {
                    $offset = $valranges[$i] + 1;
                    $len = $valranges[$i + 1] - $offset;
                    $this->parse(array_slice($values, $offset, $len));
                }
            } else {
                continue;
            }
        }
    }
    /*?
	 * Function: parse
	 * Takes an array of string arrays with indexes "tag" and "value" 
	 * The output will be a single array that has indexes equal to the values for index "tag"
	 * and values equal to the value  of index "value"
	 */
    function parse ($valuesArray)
    {
        for ($i = 0; $i < count($valuesArray); $i ++) {
            if (! defined($valuesArray[$i]["tag"])) {
                define($valuesArray[$i]["tag"], $valuesArray[$i]["value"]);
            }
        }
    }
    /*?
	 * Function: ConfigParser
	 * This is the constructor for the ConfigParser class
	 * It sets the variable $filename of the config file and calls the readConfigFile() method to read it
	 * The array returned from the readConfigFile() method is stored to global environment variables
	 */
    function __construct ()
    {
        $path = dirname(__FILE__);
        $this->fileName = $path."/Includes/configuration.xml";
        $this->readConfigFile();
    }
}
?>