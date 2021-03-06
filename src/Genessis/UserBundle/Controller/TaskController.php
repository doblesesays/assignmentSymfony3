<?php

namespace Genessis\UserBundle\Controller;

use Genessis\UserBundle\Controller\CommentController;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Genessis\UserBundle\Entity\Comment;
use Genessis\UserBundle\Form\CommentType;

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

					$message = $this->get('translator')->trans('The task has been finish.');

					return new response(
						json_encode(array('processed'=>1, 'message'=>$message)),
						200,
						array('Content-Type'=>'application/json')
					);
				}
			}else{

				if($request->isXMLHttpRequest()){

					$message = $this->get('translator')->trans('The task was already finished.');

					return new response(
						json_encode(array('processed'=>0, 'message'=>$message)),
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

	public function viewAction($id, Request $request){
		//OBTENGO Y VERIFICO LA TAREA
		$task = $this->getDoctrine()->getRepository('GenessisUserBundle:Task')->find($id);

		if(!$task){
			$message = $this->get('translator')->trans('The task does not exist.');
			throw $this->createNotFoundException($message);
		}

		//OBTENGO Y PAGINO LOS COMENTS DE LA TAREA
		$comments = $task->getComments();
		$paginator = $this->get('knp_paginator');
		$pagination = $paginator->paginate(
			$comments,
			$request->query->getInt('page', 1),
			2
		);

		//CREO EL FORM PARA AGREGAR COMMENTS NUEVOS
		$comment = new Comment();
		$commentForm = $this->createCommentForm($comment, $task->getId());

		$deleteForm = $this->createCustomForm($task->getId(), 'DELETE', 'genessis_task_delete');

		$deleteFormComment = $this->createCustomForm(':COMMENT_ID', 'DELETE', 'genessis_task_delete_comment');

		//OBTENGO EL USUARIO ASIGNADO A LA TAREA
		$user = $task->getUser();

		return $this->render('GenessisUserBundle:Task:view.html.twig', array('task'=>$task, 'user'=>$user, 'pagination'=>$pagination, 'commentForm'=>$commentForm->createView(), 'delete_form_comment'=>$deleteFormComment->createView(), 'delete_form'=>$deleteForm->createView()));
	}

	private function createCommentForm(Comment $entity, $taskId){
		$form = $this->createForm(CommentType::class, $entity, array(
			'action'=>$this->generateUrl('genessis_task_create_comment', array('taskId'=>$taskId)),
			'method'=>'POST'
		));
		return $form;
	}

	// public function createCommentAction(Request $request, $taskId){
	// 	$comment = new Comment();
	// 	$commentForm = CommentController::createCommentForm($comment, $taskId);
	// 	$commentForm->handleRequest($request);

	// 	$task = $this->getDoctrine()->getRepository('GenessisUserBundle:Task')->find($taskId);

	// 	if($commentForm->isValid()){
	// 		$user = $this->get('security.token_storage')->getToken()->getUser();
	// 		$comment->setTask($task);
	// 		$comment->setUser($user);

	// 		$em = $this->getDoctrine()->getManager();
	// 		$em->persist($comment);
	// 		$em->flush();

	// 		$message = $this->get('translator')->trans('The comment has been created.');
	// 		$this->addFlash('mensaje', $message);
	// 		return $this->redirectToRoute('genessis_task_view', array('id'=>$task->getId()));
	// 	}

	// 	//OBTENGO Y PAGINO LOS COMENTS DE LA TAREA
	// 	$comments = $this->getDoctrine()->getRepository('GenessisUserBundle:Comment')->findByTask($task->getId());
	// 	$paginator = $this->get('knp_paginator');
	// 	$pagination = $paginator->paginate(
	// 		$comments,
	// 		$request->query->getInt('page', 1),
	// 		2
	// 	);

	// 	$deleteForm = $this->createCustomForm($task->getId(), 'DELETE', 'genessis_task_delete');

	// 	//OBTENGO EL USUARIO ASIGNADO A LA TAREA
	// 	$user = $task->getUser();

	// 	return $this->render('GenessisUserBundle:Task:view.html.twig', array('task'=>$task, 'user'=>$user, 'pagination'=>$pagination, 'commentForm'=>$commentForm->createView(), 'delete_form'=>$deleteForm->createView()));
	// }

	// public function editCommentAction($id){
	// 	$em = $this->getDoctrine()->getManager();
	// 	$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

	// 	if(!$comment){
	// 		$message = $this->get('translator')->trans('Comment not found');
	// 		throw $this->createNotFoundException($message);
	// 	}

	// 	if ($comment->getUser()->getId() == $this->get('security.token_storage')->getToken()->getUser()->getId()){

	// 		$form = $this->createEditCommentForm($comment);

	// 		return $this->render('GenessisUserBundle:Comment:edit.html.twig', array('comment'=>$comment, 'form'=>$form->createView()));
	// 	}
		
	// 	$message = $this->get('translator')->trans('The comment its not made by you');
	// 	$this->addFlash('error', $message);
	// 	return $this->redirectToRoute('genessis_task_view', array('id'=>$comment->getTask()->getId()));
	// }

	// private function createEditCommentForm(Comment $entity){
	// 	$form = $this->createForm(CommentType::class, $entity, array(
	// 		'action' => $this->generateUrl('genessis_task_update_comment', array('id'=>$entity->getId())),
	// 		'method' => 'PUT'
	// 	));
	// 	return $form;
	// }

	// public function updateCommentAction($id, Request $request){
	// 	$em = $this->getDoctrine()->getManager();
	// 	$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

	// 	if(!$comment){
	// 		$message = $this->get('translator')->trans('Comment not found');
	// 		throw $this->createNotFoundException($message);
	// 	}

	// 	$form = $this->createEditCommentForm($comment);
	// 	$form->handleRequest($request);

	// 	if($form->isSubmitted() and $form->isValid()){
	// 		$em->flush();

	// 		$message = $this->get('translator')->trans('The comment has been modified');
	// 		$this->addFlash('mensaje', $message);
	// 		return $this->redirectToRoute('genessis_task_edit_comment', array('id'=>$comment->getId()));
	// 	}

	// 	$this->render('GenessisUserBundle:Comment:edit.html.twig', array('comment'=>$comment, 'form'=>$form->createView()));
	// }

	// public function deleteCommentAction($id, Request $request){
	// 	$em = $this->getDoctrine()->getManager();
	// 	$comment = $em->getRepository('GenessisUserBundle:Comment')->find($id);

	// 	if(!$comment){
 //    		$messageException = $this->get('translator')->trans('Comment not found.');
 //    		throw $this->createNotFoundException($messageException);
 //    	}

 //    	$allComments = $em->getRepository('GenessisUserBundle:Comment')->findAll();
 //    	$countComments = count($allComments);

 //    	$form = $this->createCustomForm($comment->getId(), 'DELETE', 'genessis_task_delete_comment');
 //    	$form->handleRequest($request);

 //    	if ($form->isSubmitted() and $form->isValid()){
 //    		if ($request->isXMLHttpRequest()){
 //    			$res = $this->deleteComment($comment->getUser()->getRole(), $em, $comment);

 //    			return new response(
 //    				json_encode(array('removed'=>$res['removed'], 'message'=>$res['message'], 'countComments'=>$countComments)),
 //    				200,
 //    				array('Content-Type'=>'Application/json')
 //    			);
 //    		}

 //    		$res = $this->deleteComment($comment->getUser()->getRole(), $em, $comment);

 //    		$this->addFlash($res['alert'], $res['message']);

 //    		return $this->redirectToRoute('genessis_task_view', array('id'=>$comment->getTask()->getId()));
 //    	}
	// }

	// private function deleteComment($role, $em, $comment){
	// 	if($role == 'ROLE_USER' or ($role == 'ROLE_ADMIN' and $this->get('security.token_storage')->getToken()->getUser()->getRole() == 'ROLE_ADMIN')){
 //   			$em->remove($comment);
 //   			$em->flush();

 //   			$message = $this->get('translator')->trans('The comment has been deleted.');
 //   			$removed = 1;
 //   			$alert = 'mensaje';
 //   		}elseif ($role == 'ROLE_ADMIN' and $this->get('security.token_storage')->getToken()->getUser()->getRole() == 'ROLE_USER'){
 //   			$message = $this->get('translator')->trans('The comment could not be deleted.');
 //   			$removed = 0;
 //   			$alert = 'error';
 //   		}

 //   		return array('removed'=>$removed, 'message'=>$message, 'alert'=>$alert);
	// }

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

	public function createCustomForm($id, $method, $route){
		return $this->createFormBuilder()
			->setAction($this->generateUrl($route, array('id'=>$id)))
			->setMethod($method)
			->getForm();
	}

}
