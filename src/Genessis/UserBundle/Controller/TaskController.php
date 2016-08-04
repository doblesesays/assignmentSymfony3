<?php

namespace Genessis\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Genessis\UserBundle\Entity\Task;
use Genessis\UserBundle\Form\TaskType;

class TaskController extends Controller
{

	public function indexAction(Request $request){
		$em = $this->getDoctrine()->getManager();
		$dql = "SELECT t FROM GenessisUserBundle:Task t ORDER BY t.id DESC";
		$tasks = $em->createQuery($dql);

		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$tasks,
			$request->query->getInt('page', 1),
			2
		);
		return $this->render('GenessisUserBundle:Task:index.html.twig', array('pagination'=>$pagination));
	}

	public function addAction(){
		$task = new Task();
		$form = $this->createCreateForm($task);

		return $this->render('GenessisUserBundle:Task:add.html.twig', array('form'=>$form->createView()));
	}

	private function createCreateForm(Task $entity){
		$form = $this->createForm(TaskType::class, $entity, array(
			'action'=>$this->generateUrl('genessis_task_create'),
			'method'=>'POST'
		));
		return $form;
	}

	public function createAction(Request $request){
		$task = new Task();
		$form = $this->createCreateForm($task);
		$form->handleRequest($request);

		if($form->isValid()){
			$task->setStatus(0);
			$em = $this->getDoctrine()->getManager();
			$em->persist($task);
			$em->flush();

			$this->addFlash('mensaje', 'The task has been created.');
			return $this->redirectToRoute('genessis_task_index');
		}
		return $this->render('GenessisUserBundle:Task:add.html.twig', array('form'=>$form->createView()));
	}
}
