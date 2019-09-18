<?php

/**
 * Class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
 */
class Dotdigitalgroup_Email_Model_Adminhtml_Dashboard_Content
{

    /**
     * Css style that can be used to alert based on result.
     *
     * @var
     */
    public $style;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Title to be displayed as a key for the status.
     *
     * @var
     */
    public $title;

    /**
     * Message to be displayed in the body.
     *
     * @var
     */
    public $message;

    /**
     * How to fix message.
     *
     * @var
     */
    public $howto = array();

    /**
     * Table data.
     *
     * @var array
     */
    public $table = array();

    /**
     * @return array
     */
    public function getHowto()
    {
        return $this->howto;
    }

    /**
     * @param $howto
     *
     * @return $this
     */
    public function setHowto($howto)
    {
        $this->howto[] = $howto;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @param $style
     *
     * @return $this
     */
    public function setStyle($style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table[] = $table;

        return $this;
    }

    /**
     * @return array
     */
    public function getTable()
    {
        return $this->table;
    }
}