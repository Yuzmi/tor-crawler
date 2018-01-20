<?php

namespace AppBundle\Entity;

/**
 * Resource
 */
class Resource
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $hashUrl;

    /**
     * @var string
     */
    private $title;

    /**
     * @var integer
     */
    private $lastLength;

    /**
     * @var string
     */
    private $lastError;

    /**
     * @var \DateTime
     */
    private $dateCreated;

    /**
     * @var \DateTime
     */
    private $dateChecked;

    /**
     * @var \DateTime
     */
    private $dateSeen;

    /**
     * @var \DateTime
     */
    private $dateError;

    /**
     * @var \AppBundle\Entity\Onion
     */
    private $onion;

    public function __construct($url = null) {
        $this->dateCreated = new \DateTime();
        $this->totalSuccess = 0;
        $this->countErrors = 0;

        if($url) {
            $this->setUrl($url);
        }
    }

    public function __toString() {
        return $this->url;
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
     * Set url
     *
     * @param string $url
     *
     * @return Resource
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->hashUrl = hash('sha512', $url);
        return $this;
    }

    /**
     * Get url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set hashUrl
     *
     * @param string $hashUrl
     *
     * @return Resource
     */
    public function setHashUrl($hashUrl)
    {
        $this->hashUrl = $hashUrl;

        return $this;
    }

    /**
     * Get hashUrl
     *
     * @return string
     */
    public function getHashUrl()
    {
        return $this->hashUrl;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Resource
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set lastLength
     *
     * @param integer $lastLength
     *
     * @return Resource
     */
    public function setLastLength($lastLength)
    {
        $this->lastLength = $lastLength;

        return $this;
    }

    /**
     * Get lastLength
     *
     * @return integer
     */
    public function getLastLength()
    {
        return $this->lastLength;
    }

    /**
     * Set lastError
     *
     * @param string $lastError
     *
     * @return Resource
     */
    public function setLastError($lastError)
    {
        $this->lastError = $lastError;

        return $this;
    }

    /**
     * Get lastError
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Resource
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * Set dateChecked
     *
     * @param \DateTime $dateChecked
     *
     * @return Resource
     */
    public function setDateChecked($dateChecked)
    {
        $this->dateChecked = $dateChecked;

        return $this;
    }

    /**
     * Get dateChecked
     *
     * @return \DateTime
     */
    public function getDateChecked()
    {
        return $this->dateChecked;
    }

    /**
     * Set dateSeen
     *
     * @param \DateTime $dateSeen
     *
     * @return Resource
     */
    public function setDateSeen($dateSeen)
    {
        $this->dateSeen = $dateSeen;

        return $this;
    }

    /**
     * Get dateSeen
     *
     * @return \DateTime
     */
    public function getDateSeen()
    {
        return $this->dateSeen;
    }

    /**
     * Set dateError
     *
     * @param \DateTime $dateError
     *
     * @return Resource
     */
    public function setDateError($dateError)
    {
        $this->dateError = $dateError;

        return $this;
    }

    /**
     * Get dateError
     *
     * @return \DateTime
     */
    public function getDateError()
    {
        return $this->dateError;
    }

    /**
     * Set onion
     *
     * @param \AppBundle\Entity\Onion $onion
     *
     * @return Resource
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
     * @var integer
     */
    private $countErrors;


    /**
     * Set countErrors
     *
     * @param integer $countErrors
     *
     * @return Resource
     */
    public function setCountErrors($countErrors)
    {
        $this->countErrors = $countErrors;

        return $this;
    }

    /**
     * Get countErrors
     *
     * @return integer
     */
    public function getCountErrors()
    {
        return $this->countErrors;
    }
    /**
     * @var integer
     */
    private $totalSuccess;


    /**
     * Set totalSuccess
     *
     * @param integer $totalSuccess
     *
     * @return Resource
     */
    public function setTotalSuccess($totalSuccess)
    {
        $this->totalSuccess = $totalSuccess;

        return $this;
    }

    /**
     * Get totalSuccess
     *
     * @return integer
     */
    public function getTotalSuccess()
    {
        return $this->totalSuccess;
    }
}
