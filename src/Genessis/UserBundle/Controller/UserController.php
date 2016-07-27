<?php

namespace Genessis\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserController extends Controller
{
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $users = $em->getRepository('GenessisUserBundle:User')->findAll();

        // $res = 'Lista de usuarios: <br/>';
        // foreach ($users as $user) {
        // 	$res .= 'Usuario: '. $user->getUsername() . ' - Email: ' . $user->getEmail() . '<br/>';
       	// }
       	// return new Response($res);

       	return $this->render('GenessisUserBundle:User:index.html.twig', array('users'=>$users));
    }

    public function viewAction($id){
    	$repository = $this->getDoctrine()->getRepository('GenessisUserBundle:User');
    	$user = $repository->find($id);
    	// return new Response('Usuario: ' . $user->getUsername() . 'con email ' . $user->getEmail());
    	return $this->render('GenessisUserBundle:User:view.html.twig', array('user'=>$user));
    }
}
