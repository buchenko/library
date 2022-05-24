<?php

namespace App\Controller;

use App\DataMapper\BookDataMapper;
use App\Dto\FilterBooks;
use App\Entity\Book;
use App\Dto\Book as BookDto;
use App\Form\BookType;
use App\Form\SearchBookType;
use App\Repository\BookRepository;
use App\Service\FileUploader;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/book")
 */
class BookController extends AbstractController
{
    /**
     * @Route("/", name="app_book_index", methods={"GET", "POST"})
     */
    public function index(Request $request, BookRepository $bookRepository): Response
    {
        $filterBooks = new FilterBooks();
        $form = $this->createForm(SearchBookType::class, $filterBooks);
        $form->handleRequest($request);

        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->filterByFields($filterBooks),
            'form' => $form->createView(),
            'isSubmitted'=> $form->isSubmitted(),
        ]);
    }

    /**
     * @Route("/co-authors", name="app_book_co_authors", methods={"GET"})
     * @throws Exception
     */
    public function coAuthors(Request $request, BookRepository $bookRepository): Response
    {
        $type = $request->get('type', '');
        $bookRepository->setAuthorsLimit($request->get('limit'));

        return $this->render('book/co_authors.html.twig', [
            'books' => $bookRepository->getCoAuthors($type),
            'limit' => $bookRepository->getAuthorsLimit(),
        ]);
    }

    /**
     * @Route("/new", name="app_book_new", methods={"GET", "POST"})
     */
    public function new(Request $request, BookRepository $bookRepository, FileUploader $fileUploader): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coverFile = $form->get('coverFile')->getData();
            if ($coverFile) {
                $cover = $fileUploader->upload($coverFile);
                $book->setCover($cover);
            }
            $bookRepository->add($book);

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="app_book_show", methods={"GET"})
     */
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_book_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Book $book, BookRepository $bookRepository, FileUploader $fileUploader): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coverFile = $form->get('coverFile')->getData();
            if ($coverFile) {
                $cover = $fileUploader->upload($coverFile);
                $book->setCover($cover);
            }
            $bookRepository->add($book);

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/update", name="app_book_update", methods={"GET", "POST"})
     */
    public function update(BookDto $dto, Book $book, BookRepository $bookRepository, ValidatorInterface $validator): JsonResponse
    {
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            $errorsString = '';
            foreach ($errors as $error) {
                $errorsString = $error->getMessage();
                break;
            }

            return new JsonResponse($errorsString, Response::HTTP_BAD_REQUEST);
        }

        $authorDataMapper = new BookDataMapper();
        $authorDataMapper->mapDtoToEntity($dto, $book);
        $bookRepository->add($book);

        return new JsonResponse(['data'=> 'ok']);
    }

    /**
     * @Route("/{id}", name="app_book_delete", methods={"POST"})
     */
    public function delete(Request $request, Book $book, BookRepository $bookRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $book->getId(), $request->request->get('_token'))) {
            $bookRepository->remove($book);
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
}
