<?php

//require_once ('SpinnerChief.php');


class CommentCreator {

    public static $positiveAdjectives;
    public $commentTemplate;
    public $spinner;

    function __construct() {
        self::$positiveAdjectives = array("Amazing", "Awesome", "Blithesome", "Excellent", "Fabulous", "Fantastic", "Favorable", "Fortuitous", "Great", "Incredible", "Ineffable", "Mirthful", "Outstanding", "Perfect", "Propitious", "Remarkable", "Smart", "Spectacular", "Splendid", "Stellar", "Stupendous", "Super", "Ultimate", "Unbelievable", "Wondrous");
        $this->commentTemplate = array("|A| video!!!" , "That was a |a| video.", "Glad I watched your video. It was |a|!!!", "That was an |a1| video. Simply |a2|!!!", "That was an |a1| video. Simply |a1|!!!","Thank you for posting a |a| video like this. It was exactly what I needed.","I purchased this |a| product after watching this video. Thanks for sharing!",'#|A|');
        //$this->spinner = new SpinnerChief ( "b8ed07f9276342e7b", "frostbyte07", "Neeuq011", 443 );
    }

    function getComment() {
        $template = $this -> getRandomTemplate();
        $pattern = "/\|(a([\d]?+)+)\|/i";
        //$pattern = "\|(a[\d]+)\|";
        $template = preg_replace_callback($pattern, 
        create_function(
            // single quotes are essential here,
            // or alternative escape all $ as \$
            '$matches',
            'return CommentCreator::getRandomAdjective($matches[0]);'
        ),
         $template);
         $comment = $template;
         /*
        $spunComment = $this->spinner->spinArticle ( $template, 1, 3 );
        if(stripos($spunComment, 'error')!==false){
            $comment = $template;
        }else{
            $comment = $spunComment;
        }
        */
        return ucfirst($comment);
    }

    static function starts_with_upper($str) {
        $chr = mb_substr($str, 0, 1, "UTF-8");
        return mb_strtolower($chr, "UTF-8") != $chr;
    }

    static function getRandomAdjective($match = null) {
        
        $adjectives = self::$positiveAdjectives;
        $index = array_rand($adjectives);
        $adj = $adjectives[$index];
        if (isset($match) ) {
            $match = substr($match, 1,strlen($match)-2);
            if(self::starts_with_upper($match)){
                //echo("Match (UC): $match<br>");        
                $adj = ucfirst($adj);    
            }else{
                //echo("Match (LC): $match<br>");
                $adj = strtolower ($adj);
            }
        }
        return $adj;
    }

    function getRandomTemplate() {
        $index = array_rand($this->commentTemplate);
        return $this->commentTemplate[$index];

    }

}

/*
$cmt = new CommentCreator();
for ($i=0; $i < 20; $i++) {
    echo($cmt->getComment()."<br>"); 
	
}
 */



?>

