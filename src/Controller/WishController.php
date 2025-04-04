<?php

namespace App\Controller;

use App\Repository\WishRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Wish;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Form\CommentType;
use Doctrine\ORM\EntityManager;
use App\Entity\Comment;

final class WishController extends AbstractController
{
    //Create
    #[Route('/voeux/creer', name: '_create-wish')]
    public function createWish(Request $request, EntityManagerInterface $em, FileUploader $fileUploader): Response
    {
        $wish = new Wish();
        $wish->setAuthor($this->getUser()->getUserIdentifier());
        $form = $this->createForm(\App\Form\WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $wish->setFilename($fileUploader->upload($imageFile));
            }
            $em->persist($wish);
            $em->flush();
            return $this->redirectToRoute('_wishes');
        }

        return $this->render('wish/create-wish.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    //Read
    #[Route('/voeux', name: '_wishes')]
    public function showWishes(WishRepository $wishRepository): Response
    {
        $wishes = $wishRepository->findAll();

        return $this->render('wish/wishes.html.twig', [
            'controller_name' => 'WishController',
            'wishes' => $wishes,
            'app_images_wish_directory' => $this->getParameter('app.images_wish_directory'),
        ]);
    }

    #[Route('/voeux/detail/{id}', name: '_wish-details')]
    public function showWishDetails(
        int $id, 
        WishRepository $wishRepository, 
        CommentRepository $commentRepository
    ): Response
    {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        // Création du formulaire basé sur WishType pour affichage
        $form = $this->createForm(\App\Form\WishType::class, $wish);

        //récupérer les commentaires
        $comments = $commentRepository->findBy(['wish' => $wish]);

        return $this->render('wish/wish-detail.html.twig', [
            'controller_name' => 'WishController',
            'id' => $id,
            'wish' => $wish,
            'app_images_wish_directory' => $this->getParameter('app.images_wish_directory'),
            'form' => $form->createView(),
            'comments' => $comments,
        ]);
    }

    #[Route('/voeux/detail-modifier/{id}', name: '_wish-details-modify')]
    public function showWishDetailsToModify(
        int $id, 
        WishRepository $wishRepository, 
        CommentRepository $commentRepository
    ): Response
    {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        if ($wish->getAuthor() !== $this->getUser()->getUserIdentifier()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier ce wish.');
        }

        // Création du formulaire basé sur WishType pour affichage
        $form = $this->createForm(\App\Form\WishType::class, $wish);

        //récupérer les commentaires
        $comments = $commentRepository->findBy(['wish' => $wish]);

        return $this->render('wish/update-wish.html.twig', [
            'controller_name' => 'WishController',
            'id' => $id,
            'wish' => $wish,
            'app_images_wish_directory' => $this->getParameter('app.images_wish_directory'),
            'form' => $form->createView(),
            'comments' => $comments,
        ]);
    }

    //Update
    #[Route('/voeux/modifier/{id}', name: '_update-wish')]
    public function updateWish(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        WishRepository $wishRepository,
        ValidatorInterface $validator,
        FileUploader $fileUploader
    ): Response {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        // Création du formulaire
        $form = $this->createForm(\App\Form\WishType::class, $wish);
        $form->handleRequest($request);

        // Tableau d'erreurs initial
        $validationErrors = [];

        if ($form->isSubmitted()) {
            // Validation de l'entité via les contraintes
            $validationErrors = $validator->validate($wish);

            // Récupération du fichier image
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                // Upload et suppression éventuelle de l'ancienne image
                if ($wish->getFilename()) {
                    $fileUploader->delete($wish->getFilename(), $this->getParameter('app.images_wish_directory'));
                }
                $newFilename = $fileUploader->upload($imageFile);
                $wish->setFilename($newFilename);
                $em->persist($wish);
                $em->flush();
            } elseif ($form->has('deleteImage') && $form->get('deleteImage')->getData()) {
                $fileUploader->delete($wish->getFilename(), $this->getParameter('app.images_wish_directory'));
                $wish->setFilename(null);
                $em->persist($wish);
                $em->flush();
            }

            // Enregistrement du reste si formulaire valide et aucune erreur
            if ($form->isValid() && count($validationErrors) === 0) {
                $em->flush();
                return $this->redirectToRoute('_wishes');
            }
        }

        return $this->render('wish/wish-detail.html.twig', [
            'wish' => $wish,
            'form' => $form->createView(),
            'app_images_wish_directory' => $this->getParameter('app.images_wish_directory'),
            'errors' => $validationErrors
        ]);
    }

    #[Route('/voeux/supprimer/{id}', name: '_delete-wish', methods: ['POST'])]
    public function deleteWish(int $id, EntityManagerInterface $em, WishRepository $wishRepository): Response
    {
        //trouver le wish
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        //vérifier si l'utilisateur est le créateur ou a le rôle admin
        if (
            $wish->getAuthor() !== $this->getUser()->getUserIdentifier() &&
            !in_array('ROLE_ADMIN', $this->getUser()->getRoles())
        ) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce wish.');
        }

        //suppression du wish
        $em->remove($wish);
        $em->flush();
        $this->addFlash('success', 'Le wish a été supprimé avec succès.');

        return $this->redirectToRoute('_wishes');
    }

    #[Route('/voeux/ajouter-commentaire/{id}', name: '_post-comment', methods: ['POST'])]
    public function postComment(
        WishRepository $wishRepository, 
        int $id, 
        EntityManagerInterface $em
    ): Response
    {
        //trouver le wish
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        //créer une instance de comment
        $comment = new Comment();
        $commentData = $_POST['comment'] ?? [];
        $comment->setText($commentData['text'] ?? '');
        $comment->setNote($commentData['note']);
        $comment->setWish($wish);
        $comment->setUser($this->getUser()->getUserIdentifier());
        $em->persist($comment);
        $em->flush();

        //ajouter le commentaire au wish
        $wish->addComment($comment);
        $em->persist($wish);
        $em->flush();

        return $this->redirectToRoute('_wishes');
    }

    #[Route('/voeux/ajouter-commentaire/{id}', name: '_add-comment')]
    public function addComment(
        int $id, 
        Request $request
    ): Response
    {
        $form = $this->createForm(CommentType::class);
        $form->handleRequest($request);

        return $this->render("wish/add-comment.html.twig", [
            'id' => $id,
            'form' => $form->createView(),
        ]);
    }
}
