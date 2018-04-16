<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Onion
{
    private $id;
    private $hash;
    private $dateCreated;
    private $resource;
    private $resources;
    private $onionWords;
    private $referedOnions;
    private $refererOnions;

    public function __construct() {
        $this->dateCreated = new \DateTime();
        $this->resources = new ArrayCollection();
        $this->onionWords = new ArrayCollection();
    }

    public function __toString() {
        return $this->hash.".onion";
    }

    public function getUrl($ssl = false) {
        return "http".($ssl ? 's' : '')."://".$this->hash.".onion";
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
     * Set hash
     *
     * @param string $hash
     *
     * @return Onion
     */
    public function setHash($hash)
    {
        $this->hash = $hash;

        return $this;
    }

    /**
     * Get hash
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * Set resource
     *
     * @param \AppBundle\Entity\Resource $resource
     *
     * @return Onion
     */
    public function setResource(\AppBundle\Entity\Resource $resource = null)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Get resource
     *
     * @return \AppBundle\Entity\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Onion
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
     * Add resource
     *
     * @param \AppBundle\Entity\Resource $resource
     *
     * @return Onion
     */
    public function addResource(\AppBundle\Entity\Resource $resource)
    {
        $this->resources[] = $resource;

        return $this;
    }

    /**
     * Remove resource
     *
     * @param \AppBundle\Entity\Resource $resource
     */
    public function removeResource(\AppBundle\Entity\Resource $resource)
    {
        $this->resources->removeElement($resource);
    }

    /**
     * Get resources
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * Add onionWord
     *
     * @param \AppBundle\Entity\OnionWord $onionWord
     *
     * @return Onion
     */
    public function addOnionWord(\AppBundle\Entity\OnionWord $onionWord)
    {
        $this->onionWords[] = $onionWord;

        return $this;
    }

    /**
     * Remove onionWord
     *
     * @param \AppBundle\Entity\OnionWord $onionWord
     */
    public function removeOnionWord(\AppBundle\Entity\OnionWord $onionWord)
    {
        $this->onionWords->removeElement($onionWord);
    }

    /**
     * Get onionWords
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOnionWords()
    {
        return $this->onionWords;
    }

    /**
     * Add referedOnion
     *
     * @param \AppBundle\Entity\Onion $referedOnion
     *
     * @return Onion
     */
    public function addReferedOnion(\AppBundle\Entity\Onion $referedOnion)
    {
        $this->referedOnions[] = $referedOnion;

        return $this;
    }

    /**
     * Remove referedOnion
     *
     * @param \AppBundle\Entity\Onion $referedOnion
     */
    public function removeReferedOnion(\AppBundle\Entity\Onion $referedOnion)
    {
        $this->referedOnions->removeElement($referedOnion);
    }

    /**
     * Get referedOnions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReferedOnions()
    {
        return $this->referedOnions;
    }

    /**
     * Add refererOnion
     *
     * @param \AppBundle\Entity\Onion $refererOnion
     *
     * @return Onion
     */
    public function addRefererOnion(\AppBundle\Entity\Onion $refererOnion)
    {
        $this->refererOnions[] = $refererOnion;

        return $this;
    }

    /**
     * Remove refererOnion
     *
     * @param \AppBundle\Entity\Onion $refererOnion
     */
    public function removeRefererOnion(\AppBundle\Entity\Onion $refererOnion)
    {
        $this->refererOnions->removeElement($refererOnion);
    }

    /**
     * Get refererOnions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRefererOnions()
    {
        return $this->refererOnions;
    }
}
