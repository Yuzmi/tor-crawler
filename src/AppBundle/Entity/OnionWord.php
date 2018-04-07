<?php

namespace AppBundle\Entity;

class OnionWord
{
    private $id;
    private $count;
    private $countResources;
    private $average;
    private $onion;
    private $word;

    public function __construct() {
        $this->count = 0;
        $this->countResources = 0;
        $this->average = 0;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set count
     *
     * @param integer $count
     *
     * @return OnionWord
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Get count
     *
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set countResources
     *
     * @param integer $countResources
     *
     * @return OnionWord
     */
    public function setCountResources($countResources)
    {
        $this->countResources = $countResources;

        return $this;
    }

    /**
     * Get countResources
     *
     * @return integer
     */
    public function getCountResources()
    {
        return $this->countResources;
    }

    /**
     * Set average
     *
     * @param float $average
     *
     * @return OnionWord
     */
    public function setAverage($average)
    {
        $this->average = $average;

        return $this;
    }

    /**
     * Get average
     *
     * @return float
     */
    public function getAverage()
    {
        return $this->average;
    }

    /**
     * Set onion
     *
     * @param \AppBundle\Entity\Onion $onion
     *
     * @return OnionWord
     */
    public function setOnion(\AppBundle\Entity\Onion $onion = null)
    {
        $this->onion = $onion;

        return $this;
    }

    /**
     * Get onion
     *
     * @return \AppBundle\Entity\Onion
     */
    public function getOnion()
    {
        return $this->onion;
    }

    /**
     * Set word
     *
     * @param \AppBundle\Entity\Word $word
     *
     * @return OnionWord
     */
    public function setWord(\AppBundle\Entity\Word $word = null)
    {
        $this->word = $word;

        return $this;
    }

    /**
     * Get word
     *
     * @return \AppBundle\Entity\Word
     */
    public function getWord()
    {
        return $this->word;
    }
}
