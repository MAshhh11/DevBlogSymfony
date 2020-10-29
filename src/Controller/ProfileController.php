<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\Category;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProfileController extends AbstractController
{
    /**
     * @IsGranted("ROLE_USER")
     * @Route("/profile", name="app_profile")
     */
    public function index(PostRepository $postRepository): Response
    {
        $post = $postRepository->findAll();

        return $this->render('profile/index.html.twig', [
            'posts' => $postRepository->findBy(
                ['user' => $this->getUser()],
                ['createdAt' => 'DESC'],
            ),
        ]);
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/profile/create", name="app_profile_create")
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
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => true,
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
