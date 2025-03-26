<?php

namespace App\Controller;

use App\Repository\WishRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Wish;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class WishController extends AbstractController
{
    //Create
    #[Route('/voeux/creer', name: '_create-wish')]
    public function createWish(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            //récupérer les données du formulaire
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $author = $request->request->get('author');

            //nouvel objet wish
            $wish = new Wish();
            $wish->setTitle($title);
            $wish->setDescription($description);
            $wish->setAuthor($author);
            $wish->setDateCreated(new \DateTime());
            $wish->setIsPublished(1);

            //envoyer l'objet en bdd
            $em->persist($wish);
            $em->flush();

            //rediriger vers la liste des voeux
            return $this->redirectToRoute('_wishes');
        }

        //afficher le formulaire
        return $this->render('wish/create-wish.html.twig', [
            'controller_name' => 'WishController',
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

        return $this->render('wish/wish-detail.html.twig', [
            'controller_name' => 'WishController',
            'id' => $id,
            'wish' => $wish,
        ]);
    }

    //Update
    #[Route('/voeux/modifier/{id}', name: '_update-wish')]
    public function updateWish(int $id, Request $request, EntityManagerInterface $em, WishRepository $wishRepository): Response
    {
        //récupérer le voeu à modifier
        $wish = $wishRepository->find($id);

        //le voeu n'est pas trouvé
        if (!$wish) {
            throw $this->createNotFoundException('Ce voeu n\'existe pas');
        }

        //si le formulaire est soumis
        if ($request->isMethod('POST')) {
            //récupérer le voeu
            $title = $request->request->get('title');
            $description = $request->request->get('description');
            $author = $request->request->get('author');

            //mettre à jour le voeu
            $wish->setTitle($title);
            $wish->setDescription($description);
            $wish->setAuthor($author);

            //sauvegarder les modifications
            $em->flush();

            //redirection
            return $this->redirectToRoute('_wishes');
        }

        // Afficher le formulaire avec les données existantes
        return $this->render('wish/update-wish.html.twig', [
            'wish' => $wish,
        ]);
    }

    #[Route('/voeux/modifier/{id}', name: '_delete-wish')]
    public function deleteWish(int $id, EntityManagerInterface $em, WishRepository $wishRepository): Response
    {
        $wish = $wishRepository->find($id);

        $em->remove($wish);
        $em->flush();

        return $this->redirectToRoute('_wishes');
    }
}
