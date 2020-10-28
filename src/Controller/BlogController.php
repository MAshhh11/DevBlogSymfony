<?php

namespace App\Controller;

use App\Entity\Comments;
use App\Entity\Post;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class BlogController extends AbstractController
{
    /**
     * @Route("/", name="app_home")
     */
    public function index(PostRepository $postRepository): Response
    {
        $posts = $postRepository->findAll();

        return $this->render('blog/index.html.twig', [
            'controller_name' => 'BlogController',
            'posts' => $posts,
        ]);
    }

    /**
     * @Route("/blog/{id}", name="app_blog_show", requirements={"id"="\d+"})
     */
    public function show(Post $post, Request $request, Security $security, EntityManagerInterface $em, UploaderHelper $helper)
    {
        $comment = new Comments();
        $path = $helper->asset($post);

        $form = $this->createFormBuilder($comment)
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Your Comment',
            ])
            ->add('rate', CheckboxType::class, [
                'attr' => [
                    'class' => 'form-control my-2',
                ],
                'label' => 'Like ?',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->getForm()
        ;
        $comment->setUser($security->getUser());
        $comment->setPost($post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();
        }

        return $this->render('blog/show.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/blog/create", name="app_blog_create")
     */
    public function create(Request $request, Security $security, EntityManagerInterface $em)
    {
        $post = new Post();
        $myform = $this->createFormBuilder($post)
            ->add('title', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Title',
            ])
            ->add('content', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Content',
            ])
            ->add('thumbnailFile', VichImageType::class, [
                'attr' => [
                    'class' => 'form-control-file',
                ],
                'label' => 'Image',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-success',
                ],
            ])
            ->getForm()
        ;
        $post->setUser($security->getUser());
        $post->setCreatedAt(new \DateTime());

        $myform->handleRequest($request);

        if ($myform->isSubmitted() && $myform->isValid()) {
            $post = $myform->getData();
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('blog/create.html.twig', [
            'myform' => $myform->createView(),
        ]);
    }
}
