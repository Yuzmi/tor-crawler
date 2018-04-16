<?php

namespace AppBundle\Twig;

class TextExtension extends \Twig_Extension
{
	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('shorten', array($this, 'shorten')),
            new \Twig_SimpleFilter('frenchDay', array($this, 'frenchDay')),
            new \Twig_SimpleFilter('frenchMonth', array($this, 'frenchMonth')),
            new \Twig_SimpleFilter('ago', array($this, 'ago'))
		);
	}

	public function getFunctions() {
		return array();
	}

	public function getName() {
		return 'text_extension';
	}

    public function shorten($string, $maxLength) {
        if(mb_strlen($string) > $maxLength) {
            $newMaxLength = $maxLength - 3; // '...'

            $string = mb_substr($string, 0, $newMaxLength)."...";
        }

        return $string;
    }

    private function englishToFrenchDay($day) {
        $days = array(
            "Mon" => "Lun",
            "Monday" => "Lundi",
            "Tue" => "Mar",
            "Tuesday" => "Mardi",
            "Wed" => "Mer",
            "Wednesday" => "Mercredi",
            "Thu" => "Jeu",
            "Thursday" => "Jeudi",
            "Fri" => "Ven",
            "Friday" => "Vendredi",
            "Sat" => "Sam",
            "Saturday" => "Samedi",
            "Sun" => "Dim",
            "Sunday" => "Dimanche"
        );

        if(array_key_exists($day, $days)) {
            return $days[$day];
        }

        return $day;
    }

    public function frenchDay(\DateTime $date) {
        return $this->englishToFrenchDay($date->format('l'));
    }

    private function englishToFrenchMonth($month) {
        $months = array(
            "January" => "Janvier",
            "February" => "Février",
            "March" => "Mars",
            "April" => "Avril",
            "May" => "Mai",
            "June" => "Juin",
            "July" => "Juillet",
            "August" => "Août",
            "September" => "Septembre",
            "October" => "Octobre",
            "November" => "Novembre",
            "December" => "Décembre"
        );

        if(array_key_exists($month, $months)) {
            return $months[$month];
        }

        return $month;
    }

    public function frenchMonth(\DateTime $date) {
        return $this->englishToFrenchMonth($date->format('F'));
    }

    public function ago(\DateTime $date, $short = false) {
        $now = new \DateTime();
        $diff = $now->diff($date);

        $years = $diff->format("%y");
        if($years > 1) {
            if($short) {
                return $years."y";
            } else {
                return $years." year".($years > 1 ? "s" : "");
            }
        }

        $months = $diff->format("%m");
        if($months > 1) {
            if($short) {
                return $months."mo.";
            } else {
                return $months." month".($months > 1 ? "s" : "");
            }
        }

        $days = $diff->format("%a");
        if($days > 1) {
            if($short) {
                return $days."d";
            } else {
                return $days." days";
            }
        } elseif($days == 1) {
            return $short ? "Yes." : "Yesterday";
        }

        $hours = $diff->format("%h");
        if($hours > 0) {
            if($short) {
                return $hours."h";
            } else {
                return $hours." hour".($hours > 1 ? "s" : "");
            }
        }

        $minutes = $diff->format("%i");
        if($minutes > 0) {
            if($short) {
                return $minutes."m";
            } else {
                return $minutes." minute".($minutes > 1 ? "s" : "");
            }
        }

        $seconds = $diff->format("%s");
        if($seconds > 0) {
            if($short) {
                return $seconds."s";
            } else {
                return $seconds." second".($seconds > 1 ? "s" : "");
            }
        } elseif($seconds == 0) {
            return "Now";
        }

        return $date->format("Y-m-d");
    }
}
