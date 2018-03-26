<?php

namespace AppBundle\Entity;

class ResourceError
{
    private $id;
    private $label;
    private $count;
    private $dateCreated;
    private $dateLastSeen;
    private $resource;

    public function __construct() {
        $this->dateCreated = new \DateTime();
        $this->count = 0;
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
     * Set label
     *
     * @param string $label
     *
     * @return ResourceError
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set count
     *
     * @param integer $count
     *
     * @return ResourceError
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
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return ResourceError
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
     * Set dateLastSeen
     *
     * @param \DateTime $dateLastSeen
     *
     * @return ResourceError
     */
    public function setDateLastSeen($dateLastSeen)
    {
        $this->dateLastSeen = $dateLastSeen;

        return $this;
    }

    /**
     * Get dateLastSeen
     *
     * @return \DateTime
     */
    public function getDateLastSeen()
    {
        return $this->dateLastSeen;
    }

    /**
     * Set resource
     *
     * @param \AppBundle\Entity\Resource $resource
     *
     * @return ResourceError
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
}

