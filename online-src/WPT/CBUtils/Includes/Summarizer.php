<?php
/**
 * Text summarizer
 *
 * $Id: Summarizer.php 155 2011-02-08 17:25:22Z indy $
 *
 * $LastChangedBy: indy $
 *
 * $LastChangedDate: 2011-02-08 19:25:22 +0200 (Tue, 08 Feb 2011) $
 *
 * $Rev: 155 $
 *
 * @author Indiana Jones <indy2kro@yahoo.com>
 * @version 1.0
 * @package Summarizer
 * @copyright 2011 Summarizer
 */

/**
 * Summarizer class
 *
 * @example     $summarizer = new Summarizer($options);
 *              $summarizer->loadText($text);
 *              $summary = $summarizer->run();
 *
 * @see http://www.tools4noobs.com/summarize/
 * @author Indiana Jones
 * @copyright 2011 Summarizer
 */
class Summarizer
{
    /**
     * Minimum sentence length
     */
    const OPTION_MIN_SENTENCE_LENGTH = 'min_sentence_length';

    /**
     * Minimum word length
     */
    const OPTION_MIN_WORD_LENGTH = 'min_word_length';

    /**
     * Treshold
     */
    const OPTION_TRESHOLD = 'treshold';

    /**
     * Number of best lines to return
     */
    const OPTION_FIRST_BEST = 'first_best';

    /**
     * Document is in HTML format
     */
    const OPTION_HTML = 'html';

    /**
     * Skip words file
     */
    const OPTION_SKIP_WORDS_FILE = 'skip_words_file';

    /**
     * Split text into sentences
     */
    const OPTION_SPLIT_SENTENCES = 'split_sentences';

    /**
     * Default value for minimum sentence length
     */
    const DEFAULT_MIN_SENTENCE_LENGTH = 50;

    /**
     * Default value for minimum word length
     */
    const DEFAULT_MIN_WORD_LENGTH = 4;

    /**
     * Default value for treshold
     */
    const DEFAULT_TRESHOLD = 0.7;

    /**
     * Default value for number of best lines to return
     */
    const DEFAULT_FIRST_BEST = 10;

    /**
     * Default value for split sentences
     */
    const DEFAULT_SPLIT_SENTENCES = true;

    /**
     * Default value for html
     */
    const DEFAULT_HTML = true;

    /**
     * Default value for skip words file
     */
    const DEFAULT_SKIP_WORDS_FILE = 'skip.txt';

    /**
     * Options
     * 
     * @var array
     */
    protected $_options = array();

    /**
     * Text to summarize
     *
     * @var string
     */
    protected $_text;

    /**
     * Split sentences
     *
     * @var array
     */
    protected $_sentences = array();

    /**
     * Current sentence index
     * 
     * @var integer
     */
    protected $_currentSentence = 1;

    /**
     * List of common words to skip
     *
     * @var array
     */
    protected $_skipWords = array();

    /**
     * Words extracted from sentences
     * 
     * @var array
     */
    protected $_words = array();

    /**
     * Sentence words
     * 
     * @var array
     */
    protected $_sentwords = array();

    /**
     * Maximum words frequency
     * 
     * @var integer
     */
    protected $_maxWordsFrequency = 0;

    /**
     * Summary
     * 
     * @var array
     */
    protected $_summary = array();

    /**
     * Constructor
     *
     * @param array $options Options for the constructor
     * @throws Exception
     * @return void
     */
    //public function  __construct(Array $options = array())
    public function  __construct($options = array())
    {
        $this->_treatOptions($options);
    }

    /**
     * Load text directly
     *
     * @param string $text Text to summarize
     * @return void
     */
    public function loadText($text)
    {
        // store the text internally
        $this->_text = $text;
    }

    /**
     * Load text from url
     *
     * @param string $url Url which contains the text that needs to be summarized
     * @return void
     */
    public function loadUrl($url)
    {
        // try to load text from url
        $text = file_get_contents($url);

        if (false === $text) {
            throw new Exception('Failed to read text from specified url.');
        }

        $this->_text = $text;
    }

    /**
     * Summarize text
     *
     * @throws Exception
     * @return array Returns the summary as an with each sentence a value, ordered by frequency descending
     */
    public function run()
    {
        if (empty($this->_text)) {
            // empty text, nothing to summarize
            return $this->_summary;
        }

        // clear internal variables
        $this->_clear();

        if ($this->_options[self::OPTION_SPLIT_SENTENCES]) {

            if ($this->_options[self::OPTION_HTML]) {
                // clean html tags if found
                $this->_cleanHtml();

                // extract html blocks
                $this->_extractHtml();
            } else {
                $this->_sentences[] = $this->_text;
            }

            // clean text
            $this->_cleanText();

            // split sentences
            $this->_splitSentences();
        }

        // load skip words
        $this->_loadSkipWords();

        // extract words
        $this->_extractWords();

        // count words
        $this->_countWords();

        // order sentences
        $this->_orderSentences();

        // build summary
        $this->_buildSummary();

        return $this->_summary;
    }

    /**
     * Get cleaned text
     * 
     * @return string Returns the cleaned text
     */
    public function getText()
    {
        return $this->_text;
    }

    /**
     * Get extracted sentences
     * 
     * @return array The extracted sentences
     */
    public function getSentences()
    {
        return $this->_sentences;
    }

    /**
     * Set sentences
     * 
     * @param array $sentences Sentences as an array, each sentence a value in the array
     * @return void
     */
    //public function setSentences(Array $sentences)
    public function setSentences($sentences)
    {
        $this->_sentences = $sentences;
    }

    /**
     * Get best words
     *
     * @param integer $limit Maximum number of best words (0 = return all)
     * @return array Array or best word structures with the following structure: word, frequency
     */
    public function getBestWords($limit = 10)
    {
	arsort($this->_words);

        $bestWords = array();
        $counter = 0;

        foreach ($this->_words as $word => $frequency) {
            $bestWords[] = array(
                'word' => $word,
                'frequency' => $frequency
            );

            $counter++;

            if ($limit > 0 && $counter > $limit) {
                break;
            }
        }

        return $bestWords;
    }

    /**
     * Get best sentences
     *
     * @param integer $limit Maximum number of best sentences (0 = return all)
     * @return array Array or best sentence structures with the following structure: sentence, frequency, words
     */
    public function getBestSentences($limit = 10)
    {
        $bestSentences = array();
        $counter = 0;

        $cnt = count($this->_sentwords);
        for ($i=0; $i<$cnt; $i++) {
            $bestSentences[] = array(
                'sentence' => $this->_sentwords[$i]['sentence'],
                'frequency' => $this->_sentwords[$i]['frequency'],
                'words' => $this->_sentwords[$i]['words']
            );

            $counter++;

            if ($limit > 0 && $counter > $limit) {
                break;
            }
        }

        return $bestSentences;
    }

    /**
     * Lines compare function
     *
     * @param array $line1 First line
     * @param array $line2 Second line
     * @return integer
     */
    public function linesCmp($line1, $line2)
    {
	if ($line1['frequency'] == $line2['frequency']) {
            return 0;
	}

	return ($line1['frequency'] > $line2['frequency']) ? -1 : 1;

    }

    /**
     * Clear internal variables
     *
     * @return void
     */
    protected function _clear()
    {
        $this->_sentences = array();
        $this->_currentSentence = 0;
        $this->_skipWords = array();
        $this->_words = array();
        $this->_sentwords = array();
        $this->_maxWordsFrequency = 0;
        $this->_summary = array();
    }

    /**
     * Build summary
     * 
     * @return void
     */
    protected function _buildSummary()
    {
        $limit = $this->_maxWordsFrequency * $this->_options[self::OPTION_TRESHOLD];

        $cnt = count($this->_sentwords);
        for ($i=0; $i<$cnt; $i++) {
            if ($this->_sentwords[$i]['frequency'] < $limit) {
                continue;
            }

            $this->_summary[] = $this->_sentwords[$i]['sentence'];
        }
    }

    /**
     * Order sentences by frequency
     *
     * @return void
     */
    protected function _orderSentences()
    {
        usort($this->_sentwords, array($this, 'linesCmp'));
    }

    /**
     * Count the number of word occurencies
     *
     * @return void
     */
    protected function _countWords()
    {
	$cnt = count($this->_sentwords);
	for ($i=0; $i<$cnt; $i++) {
            $totalFrequency = 0;
            if (!isset($this->_sentwords[$i]['words'])) {
                continue;
            }
            
            $sentWords = $this->_sentwords[$i]['words'];

            foreach ($sentWords as $sentWord) {
                $totalFrequency += $this->_words[$sentWord];
            }

            $this->_sentwords[$i]['frequency'] = $totalFrequency;

            if ($totalFrequency > $this->_maxWordsFrequency) {
                $this->_maxWordsFrequency = $totalFrequency;
            }
	}
    }

    /**
     * Extract words from sentences
     *
     * @return void
     */
    protected function _extractWords()
    {
        foreach ($this->_sentences as $index => $sentence) {
            $this->_currentSentence = $index;
            $this->_extractWordsFromSentence($sentence);
        }
    }

    /**
     * Extract words from sentence
     * 
     * @param string $sentence Sentence from which to extract words
     * @return void
     */
    protected function _extractWordsFromSentence($sentence)
    {
        $originalSentence = $sentence;
        $sentence = strtolower($sentence);

        $words = preg_split('/[\s;,.!?\'"]+/', $sentence);

        foreach ($words as $word) {
                if (strlen($word) < $this->_options[self::OPTION_MIN_WORD_LENGTH]) {
                    // word is too short
                    continue;
                }

                if ($this->_isWordIgnored($word)) {
                    // word is ignored
                    continue;
                }

                // increment word frecquency
                if (!isset($this->_words[$word])) {
                    $this->_words[$word] = 1;
                } else {
                    $this->_words[$word]++;
                }

                if (!isset($this->_sentwords[$this->_currentSentence])) {
                    $this->_sentwords[$this->_currentSentence] = array(
                        'words' => array(),
                        'sentence' => $originalSentence
                    );
                }

                // add word in current sentence
                if (!in_array($word, $this->_sentwords[$this->_currentSentence]['words'])) {
                    $this->_sentwords[$this->_currentSentence]['words'][] = $word;
                }
        }
    }

    /**
     * Check if a word is ignored
     * 
     * @param string $word Word checked
     * @return boolean
     */
    protected function _isWordIgnored($word)
    {
        if (isset($this->_skipWords[$word])) {
            return true;
        }

        return false;
    }

    /**
     * Load skip words
     *
     * @throws Exception
     * @return void
     */
    protected function _loadSkipWords()
    {
        $skipWordsFile = $this->_options[self::OPTION_SKIP_WORDS_FILE];

        if (!file_exists($skipWordsFile)) {
            throw new Exception('Skip words file does not exist: ' . $skipWordsFile);
        }

        $handle = fopen($skipWordsFile, 'r');
        
        if (!$handle) {
            throw new Exception('Failed to open file: ' . $skipWordsFile);
        }

        while (($buffer = fgets($handle, 4096)) !== false) {
            $word = trim($buffer);
            $this->_skipWords[$word] = true;
        }

        fclose($handle);
    }

    /**
     * Split sentences
     *
     * @return void
     */
    protected function _splitSentences()
    {
        $sentences = $this->_sentences;

        $this->_sentences = array();

	$cnt = count($sentences);
	for ($i=0; $i<$cnt; $i++) {
            $sentence = $sentences[$i];
            $sentence = preg_replace("/[ \n\r\t]{2,}/", ' ', $sentence);
            $sentence = trim(strip_tags($sentence), " <>[]#\t\r\n");

            if (strlen($sentence) >= $this->_options[self::OPTION_MIN_SENTENCE_LENGTH]) {
                $this->_splitSentence($sentence);
            }
	}
    }

    /**
     * Split sentence
     *
     * @param string $sentence Current sentence to split
     * @return void
     */
    protected function _splitSentence($sentence)
    {
	$temp = preg_split('/([\.!?]+)/i', $sentence, -1, PREG_SPLIT_DELIM_CAPTURE);

	$tempSentence = '';

	$cnt = count($temp);
	for ($i=0; $i<$cnt; $i++) {
            if (preg_match('/([\.!?]+)/i', $temp[$i])) {
                // try to split into several setences
                $tempSentence .= $temp[$i];
                $tempSentence = trim($tempSentence);

                if (strlen($tempSentence) < ($this->_options[self::OPTION_MIN_SENTENCE_LENGTH]/2)) {
                    // sentence too small, add it to the previous one
                    $this->_sentences[$this->_currentSentence-1] .= ' ' . $tempSentence;
                } else {
                    // sentences must start with uppercase letter or quote
                    if (!preg_match('/^[A-Z\"]{1}/', $tempSentence)) {
                        // add the text to the previous sentence
                        $this->_sentences[$this->_currentSentence-1] .= ' ' . $tempSentence;
                    } else {
                        // new sentence started
                        $this->_sentences[$this->_currentSentence] = $tempSentence;
                        $this->_currentSentence++;
                    }
                }
            } else {
                // can't split sentence, add it to temp buffer
                $tempSentence = $temp[$i];
            }
	}
    }

    /**
     * Extract html blocks
     *
     * @return void
     */
    protected function _extractHtml()
    {
	$lines = preg_split('/<[ \/]*div[^>]*?>/i', $this->_text);
	$lines = $this->_splitAllLines($lines, '/<[ \/]*p[^>]*?>/i');
	$lines = $this->_splitAllLines($lines, '/<[ \/]*br[^>]*?>/i');
	$lines = $this->_splitAllLines($lines, '/<[ \/]*li[^>]*?>/i');
	$lines = $this->_splitAllLines($lines, '/\|/');
	$lines = $this->_splitAllLines($lines, '/\^/');

	$cnt = count($lines);
	for ($i=0; $i<$cnt; $i++) {
            // clean sentence
            $sentence = strip_tags($lines[$i]);
            $sentence = preg_replace("/[ \n\r\t]{2,}/", ' ', $sentence);
            $sentence = trim($sentence);

            if (empty($sentence)) {
                continue;
            }

            $this->_sentences[] = $sentence;
	}
    }

    /**
     * Split all lines by regular expressions
     *
     * @param array $input Input lines
     * @param string $expression Expression used for splitting
     * @return array
     */
    protected function _splitAllLines(&$input, $expression)
    {
        $result = array();

        $cnt = count($input);
        for ($i=0; $i<$cnt; $i++) {
            $temp = preg_split($expression, $input[$i]);

            $cnt2 = count($temp);
            for ($j=0; $j<$cnt2; $j++) {
                $temp[$j] = trim($temp[$j]);

                if (strlen($temp[$j]) >= $this->_options[self::OPTION_MIN_SENTENCE_LENGTH]) {
                    $result[] = $temp[$j];
                }
            }
        }

        return $result;
    }

    /**
     * Clean plain text
     *
     * @return void
     */
    protected function _cleanText()
    {
        $cnt = count($this->_sentences);
        for ($i=0; $i<$cnt; $i++) {
            $sentence = $this->_sentences[$i];

            $sentence = trim($sentence);
            $sentence = str_replace('."', '".', $sentence);
            $this->_sentences[$i] = $sentence;
        }
    }

    /**
     * Clean html tags
     *
     * @return void
     */
    protected function _cleanHtml()
    {
        $search = array(
            '@<!--.*?-->@si',        // Strip multi-line comments NOTincluding CDATA
            '@<![CDATA[.*?]]>@si',
            '@<[ ]*script[^>]*?>.*?</script>@si',  // Strip out javascript
            '@<[ ]*style[^>]*?>.*?</style>@si',  // Strip out css
            '@<ul[^>]*?>.*?</ul>@si',   	 // Strip ul tags properly*/
            '@<form[^>]*?>.*?</form>@si',   	 // Strip forms
            "/((https?|ftp):\/\/|www\.)([^ \r\n\(\)\*\^\$!`\"'\|\[\]\{\};<>]*)/si",	// urls
            '@<img[^>]*?>@si',   		 // images
            '@<a[^>]*?>(.*?)</a>@si',   	 // links
            '@&[a-z0-9#]{2,6};@si',   	 // &xxxx;
	);

	$replace = array(
	'',	// comments
	'',	// comments2
	'',	// js
	'',	// css
	'',	// ul
	'',	 // Strip forms
	' ',	// url
	'',
	'\\1',
	'',
	);

	$this->_text = preg_replace($search, $replace, $this->_text);
    }

    /**
     * Treat given options or use default values
     * 
     * @param array $options Options given
     * @throws Exception
     * @return void
     */
    //protected function _treatOptions(Array $options = array())
    protected function _treatOptions($options = array())
    {
        // minimum sentence length
        if (isset($options[self::OPTION_MIN_SENTENCE_LENGTH])) {
            if (!is_numeric($options[self::OPTION_MIN_SENTENCE_LENGTH])) {
                throw new Exception('Minimum sentence length must be numeric.');
            }

            $this->_options[self::OPTION_MIN_SENTENCE_LENGTH] = $options[self::OPTION_MIN_SENTENCE_LENGTH];
        } else {
            $this->_options[self::OPTION_MIN_SENTENCE_LENGTH] = self::DEFAULT_MIN_SENTENCE_LENGTH;
        }
        
        // minimum word length
        if (isset($options[self::OPTION_MIN_WORD_LENGTH])) {
            if (!is_numeric($options[self::OPTION_MIN_WORD_LENGTH])) {
                throw new Exception('Minimum word length must be numeric.');
            }

            $this->_options[self::OPTION_MIN_WORD_LENGTH] = $options[self::OPTION_MIN_WORD_LENGTH];
        } else {
            $this->_options[self::OPTION_MIN_WORD_LENGTH] = self::DEFAULT_MIN_WORD_LENGTH;
        }

        // treshold
        if (isset($options[self::OPTION_TRESHOLD])) {
            if (!is_numeric($options[self::OPTION_TRESHOLD])) {
                throw new Exception('Treshold must be numeric.');
            }

            $this->_options[self::OPTION_TRESHOLD] = $options[self::OPTION_TRESHOLD];
        } else {
            $this->_options[self::OPTION_TRESHOLD] = self::DEFAULT_TRESHOLD;
        }

        // best lines
        if (isset($options[self::OPTION_FIRST_BEST])) {
            if (!is_numeric($options[self::OPTION_FIRST_BEST])) {
                throw new Exception('First best lines value must be numeric.');
            }

            $this->_options[self::OPTION_FIRST_BEST] = $options[self::OPTION_FIRST_BEST];
        } else {
            $this->_options[self::OPTION_FIRST_BEST] = self::DEFAULT_FIRST_BEST;
        }

        // html format
        if (isset($options[self::OPTION_HTML])) {
            if (!is_bool($options[self::OPTION_HTML])) {
                throw new Exception('Html value must be boolean.');
            }

            $this->_options[self::OPTION_HTML] = $options[self::OPTION_HTML];
        } else {
            $this->_options[self::OPTION_HTML] = self::DEFAULT_HTML;
        }

        // split sentences
        if (isset($options[self::OPTION_SPLIT_SENTENCES])) {
            if (!is_bool($options[self::OPTION_SPLIT_SENTENCES])) {
                throw new Exception('Split sentences value must be boolean.');
            }

            $this->_options[self::OPTION_SPLIT_SENTENCES] = $options[self::OPTION_SPLIT_SENTENCES];
        } else {
            $this->_options[self::OPTION_SPLIT_SENTENCES] = self::DEFAULT_SPLIT_SENTENCES;
        }

        // skip words file
        if (isset($options[self::OPTION_SKIP_WORDS_FILE])) {
            if (!is_file($options[self::OPTION_SKIP_WORDS_FILE])) {
                throw new Exception('Skip words file does not exist.');
            }

            $this->_options[self::OPTION_SKIP_WORDS_FILE] = $options[self::OPTION_SKIP_WORDS_FILE];
        } else {
            $this->_options[self::OPTION_SKIP_WORDS_FILE] = dirname(__FILE__) . '/' . self::DEFAULT_SKIP_WORDS_FILE;
        }
    }
}

/* EOF */