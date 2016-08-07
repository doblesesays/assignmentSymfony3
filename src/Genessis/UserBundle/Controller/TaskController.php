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

	public function customAction(Request $request){
		$idUser = $this->get('security.token_storage')->getToken()->getUser()->getId();
		$em = $this->getDoctrine()->getManager();
		$dql = "SELECT t FROM GenessisUserBundle:Task t JOIN t.user u WHERE u.id = :idUser ORDER BY t.id DESC";
		$tasks = $em->createQuery($dql)->setParameter('idUser', $idUser);

		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$tasks,
			$request->query->getInt('page', 1),
			3
		);

		$updateForm = $this->createCustomForm(':TASK_ID', 'PUT', 'genessis_task_process');

		return $this->render('GenessisUserBundle:Task:custom.html.twig', array('pagination'=>$pagination, 'update_form'=>$updateForm->createView()));
	}

	public function processAction($id, Request $request){
		$em = $this->getDoctrine()->getManager();
		$task = $em->getRepository('GenessisUserBundle:Task')->find($id);

		if (!$task){
			throw $this->createNotFoundException('Task not found');
		}

		$form = $this->createCustomForm($task->getId(), 'PUT', 'genessis_task_process');
		$form->handleRequest($request);

		if ($form->isSubmitted() and $form->isValid()){
			if ($task->getStatus()==0){
				$task->setStatus(1);
				$em->flush();

				if($request->isXMLHttpRequest()){
					return new response(
						json_encode(array('processed'=>1)),
						200,
						array('Content-Type'=>'application/json')
					);
				}
			}else{
				if($request->isXMLHttpRequest()){
					return new response(
						json_encode(array('processed'=>0)),
						200,
						array('Content-Type'=>'application/json')
					);
				}
			}
		}
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

			$message = $this->get('translator')->trans('The task has been created.');
			$this->addFlash('mensaje', $message);
			return $this->redirectToRoute('genessis_task_index');
		}
		return $this->render('GenessisUserBundle:Task:add.html.twig', array('form'=>$form->createView()));
	}

	public function viewAction($id){
		$task = $this->getDoctrine()->getRepository('GenessisUserBundle:Task')->find($id);

		if(!$task){
			$message = $this->get('translator')->trans('The task does not exist.');
			throw $this->createNotFoundException($message);
		}

		$deleteForm = $this->createCustomForm($task->getId(), 'DELETE', 'genessis_task_delete');

		$user = $task->getUser();

		return $this->render('GenessisUserBundle:Task:view.html.twig', array('task'=>$task, 'user'=>$user, 'delete_form'=>$deleteForm->createView()));
	}

	public function editAction($id){
		$em = $this->getDoctrine()->getManager();
		$task = $em->getRepository('GenessisUserBundle:Task')->find($id);

		if(!$task){
			$message = $this->get('translator')->trans('Task not found');
			throw $this->createNotFoundException($message);
		}

		$form = $this->createEditForm($task);

		return $this->render('GenessisUserBundle:Task:edit.html.twig', array('task'=>$task, 'form'=>$form->createView()));
	}

	private function createEditForm(Task $entity){
		$form = $this->createForm(TaskType::class, $entity, array(
			'action'=> $this->generateUrl('genessis_task_update', array('id'=>$entity->getId())),
			'method'=>'PUT'
		));
		return $form;
	}

	public function updateAction($id, Request $request){
		$em = $this->getDoctrine()->getManager();
		$task = $em->getRepository('GenessisUserBundle:Task')->find($id);

		if(!$task){
			$message = $this->get('translator')->trans('Task not found');
			throw $this->createNotFoundException($message);
		}
		$form = $this->createEditForm($task);
		$form->handleRequest($request);

		if($form->isSubmitted() and $form->isValid()){
			$task->setStatus(0);
			$em->flush();

			$message = $this->get('translator')->trans('The task has been modified');
			$this->addFlash('mensaje', $message);
			return $this->redirectToRoute('genessis_task_edit', array('id'=>$task->getId()));
		}
		$this->render('GenessisUserBundle:Task:edit.html.twig', array('task'=>$task, 'form'=>$form->createView()));
	}

	public function deleteAction(Request $request, $id){
		$em = $this->getDoctrine()->getManager();
		$task = $em->getRepository('GenessisUserBundle:Task')->find($id);

		if(!$task){
			$message = $this->get('translator')->trans('Task not found');
			throw $this->createNotFoundException($message);
		}
		$form = $this->createCustomForm($task->getId(), 'DELETE', 'genessis_task_delete');
		$form->handleRequest($request);

		if($form->isSubmitted() and $form->isValid()){
			$em->remove($task);
			$em->flush();

			$message = $this->get('translator')->trans('The task has been deleted');
			$this->addFlash('mensaje', $message);

			return $this->redirectToRoute('genessis_task_index');
		}
	}

	private function createCustomForm($id, $method, $route){
		return $this->createFormBuilder()
			->setAction($this->generateUrl($route, array('id'=>$id)))
			->setMethod($method)
			->getForm();
	}

}
