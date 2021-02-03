<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Author;
use App\Entity\Book;

class BookController extends AbstractController
{
    /**
     * @Route("/book", name="book_index")
     */
    public function index(request $r): Response
    {
        // $books = $this->getDoctrine()
        //     ->getRepository(Book::class)
        //     ->findAll();

        $authors = $this->getDoctrine()
            ->getRepository(Author::class)
            ->findBy([],['surname'=>'asc']);

        $books = $this->getDoctrine()
            ->getRepository(Book::class);
            if ($r->query->get('author_id') !== null && $r->query->get('author_id') != 0) 
                $books = $books->findBy(['author_id' => $r->query->get('author_id')], ['title' => 'asc']);
            elseif ($r->query->get('author_id') == 0) $books = $books->findAll();
            else $books = $books->findAll();

        return $this->render('book/index.html.twig', [
            'books' => $books,
            'authors' => $authors,
            'authorId' => $r->query->get('author_id') ?? 0,
            'success' => $r->getSession()->getFlashBag()->get('success', [])
        ]);
    }

    /**
     * @Route("/book/create", name="book_create", methods={"GET"})
     */
    public function create(request $r): Response
    {
        $authors = $this->getDoctrine()
            ->getRepository(Author::class)
            ->findBy([],['surname'=>'asc']);

        return $this->render('book/create.html.twig', [
            'authors' => $authors,
            'errors' => $r->getSession()->getFlashBag()->get('errors', [])
        ]);
    }

    /**
     * @Route("/book/store", name="book_store", methods={"POST"})
     */
    public function store(request $r, ValidatorInterface $validator): Response
    {
        $submittedToken = $r->request->get('token');
        if (!$this->isCsrfTokenValid('', $submittedToken)) $r->getSession()->getFlashBag()->add('errors', 'Invalid token.');

        $author = $this->getDoctrine()
        ->getRepository(Author::class)
        ->find($r->request->get('book_author_id'));

        if($author == null) $r->getSession()->getFlashBag()->add('errors', 'Author must be selected.');
        
        $book = new Book;
        $book
            ->setTitle($r->request->get('book_title'))
            ->setIsbn($r->request->get('book_isbn'))
            ->setPages((int)$r->request->get('book_pages'))
            ->setAbout($r->request->get('book_about'))
            ->setAuthor($author);

        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            return $this->redirectToRoute('book_create');
        }
        if (!$this->isCsrfTokenValid('', $submittedToken)) return $this->redirectToRoute('book_create');
        if($author == null) return $this->redirectToRoute('book_create');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'Book was added.');

        return $this->redirectToRoute('book_index');
    }

    /**
     * @Route("/book/edit/{id}", name="book_edit", methods={"GET"})
     */
    public function edit(int $id, request $r): Response
    {
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);

        $authors = $this->getDoctrine()
            ->getRepository(Author::class)
            ->findBy([],['surname'=>'asc']);

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'authors' => $authors,
            'errors' => $r->getSession()->getFlashBag()->get('errors', []),
            'success' => $r->getSession()->getFlashBag()->get('success', [])
        ]);
    }

    /**
     * @Route("/book/update/{id}", name="book_update", methods={"POST"})
     */
    public function update(request $r, $id, ValidatorInterface $validator): Response
    {
        $submittedToken = $r->request->get('token');
        if (!$this->isCsrfTokenValid('', $submittedToken)) $r->getSession()->getFlashBag()->add('errors', 'Invalid token.');

        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);

        $author = $this->getDoctrine()
            ->getRepository(Author::class)
            ->find($r->request->get('books_author'));
        
        $book
            ->setTitle($r->request->get('book_title'))
            ->setIsbn($r->request->get('book_isbn'))
            ->setPages($r->request->get('book_pages'))
            ->setAbout($r->request->get('book_about'))
            ->setAuthor($author);
            
        $errors = $validator->validate($book);
        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $r->getSession()->getFlashBag()->add('errors', $error->getMessage());
            }
            return $this->redirectToRoute('book_edit', ['id'=>$book->getId()]);
        }
        if (!$this->isCsrfTokenValid('', $submittedToken)) return $this->redirectToRoute('book_edit', ['id'=>$book->getId()]);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($book);
        $entityManager->flush();

        $r->getSession()->getFlashBag()->add('success', 'Book was updated.');

        return $this->redirectToRoute('book_index');
    }

    /**
     * @Route("/book/delete/{id}", name="book_delete", methods={"POST"})
     */
    public function delete($id): Response
    {
        $book = $this->getDoctrine()
            ->getRepository(Book::class)
            ->find($id);
        
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->remove($book);
        $entityManager->flush();

        return $this->redirectToRoute('book_index');
    }
}
