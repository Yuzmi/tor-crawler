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
    private $onionWords;

    public function __construct($string = null)
    {
        $this->resourceWords = new ArrayCollection();
        $this->onionWords = new ArrayCollection();
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

    /**
     * Add onionWord
     *
     * @param \AppBundle\Entity\OnionWord $onionWord
     *
     * @return Word
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
}
