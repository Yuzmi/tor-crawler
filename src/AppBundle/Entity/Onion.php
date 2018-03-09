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

    public function __construct() {
        $this->dateCreated = new \DateTime();
        $this->resources = new ArrayCollection();
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
}
