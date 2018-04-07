<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Word
{
    private $id;
    private $string;
    private $length;
    private $dateCreated;
    private $resourceWords;

    public function __construct($string = null)
    {
        $this->resourceWords = new ArrayCollection();
        $this->dateCreated = new \DateTime();
        if($string !== null) {
            $this->setString($string);
        }
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
     * Set string
     *
     * @param string $string
     *
     * @return Word
     */
    public function setString($string)
    {
        $this->string = $string;
        $this->length = mb_strlen($string);

        return $this;
    }

    /**
     * Get string
     *
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * Set length
     *
     * @param integer $length
     *
     * @return Word
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return Word
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
     * Add resourceWord
     *
     * @param \AppBundle\Entity\ResourceWord $resourceWord
     *
     * @return Word
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
}
