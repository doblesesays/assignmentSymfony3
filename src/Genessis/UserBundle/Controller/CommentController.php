<?php

namespace Genessis\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Genessis\UserBundle\Entity\Comment;
use Genessis\UserBundle\Entity\Task;
use Genessis\UserBundle\Form\CommentType;

class CommentController extends Controller
{
	public function createAction(Request $request, $taskId){
		$comment = new Comment();
		$form = $this->createCommentForm($comment, $taskId);
		$form->handleRequest($request);

		if($form->isValid()){
			$task = $this->getDoctrine()->getRepository('GenessisUserBundle:Task')->find($taskId);
			$user = $this->get('security.token_storage')->getToken()->getUser();
			$comment = setTask($task);
			$comment = setUser($user);

			$em = $this->getDoctrine()->getManager();
			$em->persist($comment);
			$em->flush();

			$message = $this->get('translator')->trans('The comment has been created.');
			$this->addFlash('mensaje', $message);
			return $this->redirectToRoute('genessis_task_edit', array('id'=>$task->getId()));
		}
		return $this->redirectToRoute('genessis_user_homepage');
	}

	private function createCommentForm(Comment $entity, $taskId){
		$form = $this->createForm(CommentType::class, $entity, array(
			'action'=>$this->generateUrl('genessis_task_create_comment', array('taskId'=>$taskId)),
			'method'=>'POST'
		));
		return $form;
	}
}
