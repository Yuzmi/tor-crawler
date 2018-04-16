<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Resource
{
    private $id;
    private $url;
    private $hashUrl;
    private $title;
    private $lastLength;
    private $lastError;
    private $dateCreated;
    private $dateChecked;
    private $dateFirstSeen;
    private $dateLastSeen;
    private $dateError;
    private $onion;
    private $errors;
    private $resourceWords;
    private $totalSuccess;
    private $countErrors;
    private $referedResources;
    private $refererResources;

    public function __construct($url = null) {
        $this->errors = new ArrayCollection();
        $this->resourceWords = new ArrayCollection();
        $this->referedResources = new ArrayCollection();
        $this->refererResources = new ArrayCollection();
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

    public function setDateSeen($dateSeen) {
        if(!$this->dateFirstSeen) {
            $this->dateFirstSeen = $dateSeen;
        }
        if($this->dateLastSeen < $dateSeen) {
            $this->dateLastSeen = $dateSeen;
        }
    }

    public function setDateFirstSeen($dateFirstSeen) {
        $this->dateFirstSeen = $dateFirstSeen;
        return $this;
    }
    public function getDateFirstSeen() {
        return $this->dateFirstSeen;
    }

    public function setDateLastSeen($dateLastSeen) {
        $this->dateLastSeen = $dateLastSeen;
        return $this;
    }
    public function getDateLastSeen() {
        return $this->dateLastSeen;
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

    /**
     * Add error
     *
     * @param \AppBundle\Entity\ResourceError $error
     *
     * @return Resource
     */
    public function addError(\AppBundle\Entity\ResourceError $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * Remove error
     *
     * @param \AppBundle\Entity\ResourceError $error
     */
    public function removeError(\AppBundle\Entity\ResourceError $error)
    {
        $this->errors->removeElement($error);
    }

    /**
     * Get errors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Add resourceWord
     *
     * @param \AppBundle\Entity\ResourceWord $resourceWord
     *
     * @return Resource
     */
    public function addResourceWord(\AppBundle\Entity\ResourceWord $resourceWord)
    {
        $this->resourceWords[] = $resourceWord;

        return $this;
    }

    /**
     * Remove resourceWord
     *
     * @param \AppBundle\Entity\ResourceWord $resourceWord
     */
    public function removeResourceWord(\AppBundle\Entity\ResourceWord $resourceWord)
    {
        $this->resourceWords->removeElement($resourceWord);
    }

    /**
     * Get resourceWords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResourceWords()
    {
        return $this->resourceWords;
    }

    /**
     * Add referedResource
     *
     * @param \AppBundle\Entity\Resource $referedResource
     *
     * @return Resource
     */
    public function addReferedResource(\AppBundle\Entity\Resource $referedResource)
    {
        $this->referedResources[] = $referedResource;

        return $this;
    }

    /**
     * Remove referedResource
     *
     * @param \AppBundle\Entity\Resource $referedResource
     */
    public function removeReferedResource(\AppBundle\Entity\Resource $referedResource)
    {
        $this->referedResources->removeElement($referedResource);
    }

    /**
     * Get referedResources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReferedResources()
    {
        return $this->referedResources;
    }

    /**
     * Add refererResource
     *
     * @param \AppBundle\Entity\Resource $refererResource
     *
     * @return Resource
     */
    public function addRefererResource(\AppBundle\Entity\Resource $refererResource)
    {
        $this->refererResources[] = $refererResource;

        return $this;
    }

    /**
     * Remove refererResource
     *
     * @param \AppBundle\Entity\Resource $refererResource
     */
    public function removeRefererResource(\AppBundle\Entity\Resource $refererResource)
    {
        $this->refererResources->removeElement($refererResource);
    }

    /**
     * Get refererResources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRefererResources()
    {
        return $this->refererResources;
    }
}
