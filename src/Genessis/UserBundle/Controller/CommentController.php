<?php

namespace Genessis\UserBundle\Controller;

use Genessis\UserBundle\Controller\TaskController;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Genessis\UserBundle\Entity\Task;
use Genessis\UserBundle\Form\TaskType;

use Genessis\UserBundle\Entity\Comment;
use Genessis\UserBundle\Form\CommentType;

class CommentController extends Controller
{

	public function createCommentAction(Request $request, $taskId){
		$comment = new Comment();
		$commentForm = $this->createCommentForm($comment, $taskId);
		$commentForm->handleRequest($request);

		$task = $this->getDoctrine()->getRepository('GenessisUserBundle:Task')->find($taskId);

		if($commentForm->isValid()){
			$user = $this->get('security.token_storage')->getToken()->getUser();
			$comment->setTask($task);
			$comment->setUser($user);

			$em = $this->getDoctrine()->getManager();
			$em->persist($comment);
			$em->flush();

			$message = $this->get('translator')->trans('The comment has been created.');
			$this->addFlash('mensaje', $message);
			return $this->redirectToRoute('genessis_task_view', array('id'=>$task->getId()));
		}

		//OBTENGO Y PAGINO LOS COMENTS DE LA TAREA
		$comments = $this->getDoctrine()->getRepository('GenessisUserBundle:Comment')->findByTask($task->getId());
		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$comments,
			$request->query->getInt('page', 1),
			2
		);

		$deleteForm = TaskController::createCustomForm($task->getId(), 'DELETE', 'genessis_task_delete');

		//OBTENGO EL USUARIO ASIGNADO A LA TAREA
		$user = $task->getUser();

		return $this->render('GenessisUserBundle:Task:view.html.twig', array('task'=>$task, 'user'=>$user, 'pagination'=>$pagination, 'commentForm'=>$commentForm->createView(), 'delete_form'=>$deleteForm->createView()));
	}

	private function createCommentForm(Comment $entity, $taskId){
		$form = $this->createForm(CommentType::class, $entity, array(
			'action'=>$this->generateUrl('genessis_task_create_comment', array('taskId'=>$taskId)),
			'method'=>'POST'
		));
		return $form;
	}

	public function editCommentAction($id){
		$em = $this->getDoctrine()->getManager();
		$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

		if(!$comment){
			$message = $this->get('translator')->trans('Comment not found');
			throw $this->createNotFoundException($message);
		}

		if ($comment->getUser()->getId() == $this->get('security.token_storage')->getToken()->getUser()->getId()){

			$form = $this->createEditCommentForm($comment);

			return $this->render('GenessisUserBundle:Comment:edit.html.twig', array('comment'=>$comment, 'form'=>$form->createView()));
		}
		
		$message = $this->get('translator')->trans('The comment its not made by you');
		$this->addFlash('error', $message);
		return $this->redirectToRoute('genessis_task_view', array('id'=>$comment->getTask()->getId()));
	}

	private function createEditCommentForm(Comment $entity){
		$form = $this->createForm(CommentType::class, $entity, array(
			'action' => $this->generateUrl('genessis_task_update_comment', array('id'=>$entity->getId())),
			'method' => 'PUT'
		));
		return $form;
	}

	public function updateCommentAction($id, Request $request){
		$em = $this->getDoctrine()->getManager();
		$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

		if(!$comment){
			$message = $this->get('translator')->trans('Comment not found');
			throw $this->createNotFoundException($message);
		}

		$form = $this->createEditCommentForm($comment);
		$form->handleRequest($request);

		if($form->isSubmitted() and $form->isValid()){
			$em->flush();

			$message = $this->get('translator')->trans('The comment has been modified');
			$this->addFlash('mensaje', $message);
			return $this->redirectToRoute('genessis_task_edit_comment', array('id'=>$comment->getId()));
		}

		$this->render('GenessisUserBundle:Comment:edit.html.twig', array('comment'=>$comment, 'form'=>$form->createView()));
	}

	public function deleteCommentAction($id, Request $request){
		$em = $this->getDoctrine()->getManager();
		$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

		if(!$comment){
    		$messageException = $this->get('translator')->trans('Comment not found.');
    		throw $this->createNotFoundException($messageException);
    	}

    	$allComments = $em->getRepository('GenessisUserBundle:Comment')->findAll();
    	$countComments = count($allComments);

    	$form = TaskController::createCustomForm($comment->getId(), 'DELETE', 'genessis_task_delete_comment');
    	$form->handleRequest($request);

    	if ($form->isSubmitted() and $form->isValid()){
    		if ($request->isXMLHttpRequest()){
    			$res = $this->deleteComment($comment->getUser()->getRole(), $em, $comment);

    			return new response(
    				json_encode(array('removed'=>$res['removed'], 'message'=>$res['message'], 'countComments'=>$countComments)),
    				200,
    				array('Content-Type'=>'Application/json')
    			);
    		}

    		$res = $this->deleteComment($comment->getUser()->getRole(), $em, $comment);

    		$this->addFlash($res['alert'], $res['message']);

    		return $this->redirectToRoute('genessis_task_view', array('id'=>$comment->getTask()->getId()));
    	}
	}

	private function deleteComment($role, $em, $comment){
		if($role == 'ROLE_USER' or ($role == 'ROLE_ADMIN' and $this->get('security.token_storage')->getToken()->getUser()->getRole() == 'ROLE_ADMIN')){
   			$em->remove($comment);
   			$em->flush();

   			$message = $this->get('translator')->trans('The comment has been deleted.');
   			$removed = 1;
   			$alert = 'mensaje';
   		}elseif ($role == 'ROLE_ADMIN' and $this->get('security.token_storage')->getToken()->getUser()->getRole() == 'ROLE_USER'){
   			$message = $this->get('translator')->trans('The comment could not be deleted.');
   			$removed = 0;
   			$alert = 'error';
   		}

   		return array('removed'=>$removed, 'message'=>$message, 'alert'=>$alert);
	}
}
