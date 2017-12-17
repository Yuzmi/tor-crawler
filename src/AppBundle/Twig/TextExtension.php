<?php

namespace AppBundle\Twig;

class TextExtension extends \Twig_Extension
{
	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('shorten', array($this, 'shorten')),
            new \Twig_SimpleFilter('frenchDay', array($this, 'frenchDay')),
            new \Twig_SimpleFilter('frenchMonth', array($this, 'frenchMonth')),
            new \Twig_SimpleFilter('relativeDate', array($this, 'relativeDate')),
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

    public function relativeDate(\DateTime $date) {
        $now = new \DateTime();
        if($now < $date) {
            return "dans le futur";
        }

        $days = $now->diff($date)->format("%a");
        if($days > 1) {
            return "il y a ".$days." jours";
        } elseif($days == 1) {
            return "hier";
        }

        $hours = $now->diff($date)->format("%h");
        if($hours > 0) {
            return "il y a ".$hours." heure".($hours > 1 ? "s" : "");
        }

        $minutes = $now->diff($date)->format("%i");
        if($minutes > 0) {
            return "il y a ".$minutes." minute".($minutes > 1 ? "s" : "");
        }

        $seconds = $now->diff($date)->format("%s");
        if($seconds > 0) {
            return "il y a ".$seconds." seconde".($seconds > 1 ? "s" : "");
        } elseif($seconds == 0) {
            return "à l'instant";
        }

        return $date->format("Y-m-d H:i:s");
    }
}
