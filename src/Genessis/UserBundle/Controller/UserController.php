<?php

namespace Genessis\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Genessis\UserBundle\Entity\User;
use Genessis\UserBundle\Form\UserType;

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

    public function addAction(){
    	$user = new User();
		$form = $this->createCreateForm($user);

		return $this->render('GenessisUserBundle:User:add.html.twig', array('form'=>$form->createView()));
    }

    private function createCreateForm(User $entity){
    	$form = $this->createForm(UserType::class, $entity, array(
    			'action' => $this->generateUrl('genessis_user_create'),
    			'method' => 'POST'
    		));
    	return $form;
    }

    public function createAction(Request $request){
    	$user = new User();
    	$form = $this->createCreateForm($user);
    	$form->handleRequest($request);

    	if($form->isValid()){
    		$em = $this->getDoctrine()->getManager();
    		$em->persist($user);
    		$em->flush();
    		
    		return $this->redirectToRoute('genessis_user_index');
    	}
    	return $this->render('GenessisUserBundle:User:add.html.twig', array('form'=>$form->createView()));
    } 

    public function viewAction($id){
    	$repository = $this->getDoctrine()->getRepository('GenessisUserBundle:User');
    	$user = $repository->find($id);
    	// return new Response('Usuario: ' . $user->getUsername() . 'con email ' . $user->getEmail());
    	return $this->render('GenessisUserBundle:User:view.html.twig', array('user'=>$user));
    }
}
