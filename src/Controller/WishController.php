<?php

namespace App\Controller;

use App\Repository\WishRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Wish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class WishController extends AbstractController
{
    //Create
    #[Route('/voeux/creer', name: '_create-wish')]
    public function createWish(Request $request, EntityManagerInterface $em): Response
    {
        $wish = new Wish();
        $form = $this->createForm(\App\Form\WishType::class, $wish);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
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
        ]);
    }

    #[Route('/voeux/detail/{id}', name: '_wish-details')]
    public function showWishDetails(int $id, WishRepository $wishRepository): Response
    {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        // Création du formulaire basé sur WishType pour affichage
        $form = $this->createForm(\App\Form\WishType::class, $wish);

        return $this->render('wish/wish-detail.html.twig', [
            'controller_name' => 'WishController',
            'id' => $id,
            'wish' => $wish,
            'form' => $form->createView()
        ]);
    }

    //Update
    #[Route('/voeux/modifier/{id}', name: '_update-wish')]
    public function updateWish(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        WishRepository $wishRepository,
        ValidatorInterface $validator
    ): Response {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }

        // Initialiser $validationErrors à un tableau vide
        $validationErrors = [];

        // Création du formulaire basé sur WishType et traitement de la requête
        $form = $this->createForm(\App\Form\WishType::class, $wish);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Validation via le form builder ainsi que les contraintes de l'entité
            $validationErrors = $validator->validate($wish);
            if ($form->isValid() && count($validationErrors) === 0) {
                $em->flush();
                return $this->redirectToRoute('_wishes');
            }
        }

        return $this->render('wish/wish-detail.html.twig', [
            'wish' => $wish,
            'form' => $form->createView(),
            'errors' => $validationErrors  // Passer les erreurs à la vue
        ]);
    }

    #[Route('/voeux/supprimer/{id}', name: '_delete-wish', methods: ['POST'])]
    public function deleteWish(int $id, EntityManagerInterface $em, WishRepository $wishRepository): Response
    {
        $wish = $wishRepository->find($id);
        if (!$wish) {
            throw $this->createNotFoundException('Le wish demandé n\'existe pas.');
        }
        $em->remove($wish);
        $em->flush();
        $this->addFlash('success', 'Le wish a été supprimé avec succès.');

        return $this->redirectToRoute('_wishes');
    }
}
