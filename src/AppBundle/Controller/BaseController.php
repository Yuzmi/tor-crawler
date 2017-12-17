<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Csrf\CsrfToken;

class BaseController extends Controller
{
	public function getEm() {
		return $this->getDoctrine()->getManager();
	}

    public function getRepo($entity) {
    	return $this->getEm()->getRepository('AppBundle:'.$entity);
    }

    public function isLogged() {
    	return $this->get('security.authorization_checker')->isGranted('ROLE_USER');
    }

    public function isLoggedPro() {
        return $this->isLogged() && $this->get('security.authorization_checker')->isGranted('ROLE_PRO');
    }

    public function isLoggedAdmin() {
        return $this->isLogged() && $this->get('security.authorization_checker')->isGranted('ROLE_ADMIN');
    }

    public function addFm($message, $type = "notice", $transParameters = array(), $transDomain = "flashmessage") {
    	$this->get('session')->getFlashBag()->add(
            $type, 
            $this->get('translator')->trans(
                $message,
                $transParameters,
                $transDomain
            )
        );
    }

    public function isTokenValid($id, $value) {
        return $this->get('security.csrf.token_manager')->isTokenValid(new CsrfToken($id, $value));
    }
}
