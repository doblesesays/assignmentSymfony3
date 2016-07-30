<?php

namespace Genessis\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Genessis\UserBundle\Entity\User;
use Genessis\UserBundle\Form\UserType;

class UserController extends Controller
{
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        // $users = $em->getRepository('GenessisUserBundle:User')->findAll();

       	$dql = "SELECT u FROM GenessisUserBundle:User u";
       	$users = $em->createQuery($dql);

       	$paginator = $this->get('knp_paginator');
       	$pagination = $paginator->paginate(
       		$users,
       		$request->query->getInt('page', 1),
       		10
       	);

       	return $this->render('GenessisUserBundle:User:index.html.twig', array('pagination'=>$pagination));
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

    		$password = $form->get('password')->getData();
    		$encoder = $this->container->get('security.password_encoder');
    		$encoded = $encoder->encodePassword($user, $password);

    		$user->setPassword($encoded);

    		$em = $this->getDoctrine()->getManager();
    		$em->persist($user);
    		$em->flush();

    		$successMessage = $this->get('translator')->trans('The user has been created.');
    		$this->addFlash('mensaje', $successMessage);

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
