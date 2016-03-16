<?php

class Dotdigitalgroup_Email_Model_Customer_Review_Rating
{

    /**
     * @var int
     */
    public $ratingScore;

    /**
     * constructor
     *
     * @param $rating
     */
    public function __construct($rating)
    {
        $this->setRatingScore($rating->getValue());
    }

    /**
     * @param $score
     *
     * @return $this
     */
    public function setRatingScore($score)
    {
        $this->ratingScore = (int)$score;

        return $this;
    }

    /**
     * @return int
     */
    public function getRatingScore()
    {
        return $this->ratingScore;
    }

    /**
     * @return array
     */
    public function expose()
    {
        return get_object_vars($this);
    }
}