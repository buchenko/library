<?php

namespace App\Repository;

use App\Dto\FilterBooks;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public const AUTHORS_LIMIT = 3;

    private int $authorsLimit = BookRepository::AUTHORS_LIMIT;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Book $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Book $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @param FilterBooks $filterBooks
     *
     * @return float|int|mixed|string
     */
    public function filterByFields(FilterBooks $filterBooks)
    {
        $query = $this->createQueryBuilder('b');
        if ($filterBooks->getTitle()) {
            $query->andWhere('b.title like :title')
                ->setParameter('title', '%' . $filterBooks->getTitle() . '%');
        }
        if ($filterBooks->getDescription()) {
            $query->andWhere('b.description like :description')
                ->setParameter('description', '%' . $filterBooks->getDescription() . '%');
        }
        if ($filterBooks->getYear()) {
            $query->andWhere('b.year = :year')
                ->setParameter('year', $filterBooks->getYear());
        }
        if ($filterBooks->getId()) {
            $query->andWhere('b.id = :id')
                ->setParameter('id', $filterBooks->getId());
        }
        if ($filterBooks->getAuthor()) {
            $query->join('b.authors', 'a', Join::WITH, 'a.id = :authorId')
                ->setParameter('authorId', $filterBooks->getAuthor()->getId());
        }

        $query->orderBy('b.id', 'ASC');

        return $query->getQuery()->getResult();
    }


    /**
     * @param string $type
     *
     * @return Result|float|int|mixed|string
     * @throws Exception
     */
    public function getCoAuthors(string $type)
    {
        switch ($type) {
            case 'orm':
                $authors = $this->getCoAuthorsOrm();
                break;
            case 'raw':
            default:
                $authors = $this->getCoAuthorsRaw();
                break;
        }

        return $authors;
    }

    /**
     * @return float|int|mixed[]|string
     */
    private function getCoAuthorsOrm()
    {
        $query = $this->createQueryBuilder('b');
        $query->select(['b.id', 'b.title', 'b.description', 'b.year', 'b.cover', 'count_authors' => 'count(a.id) count_authors']);
        $query->join('b.authors', 'a', Join::WITH);
        $query->groupBy('b.id');
        $query->having('count_authors >= :count_limit')->setParameter('count_limit', $this->getAuthorsLimit());
        $query->orderBy('b.id', 'ASC');

        return $query->getQuery()->getArrayResult();
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getCoAuthorsRaw(): array
    {
        $entityManager = $this->getEntityManager();
        $connection = $entityManager->getConnection();
        $sql = "select b.*, count(ba.author_id) count_authors
                from books b
                join book_author ba on b.id = ba.book_id
                group by b.id
                having count_authors >= :count_limit";
        $values = ['count_limit' => $this->getAuthorsLimit()];
        $stmt = $connection->prepare($sql);

        return $stmt->executeQuery($values)->fetchAllAssociative();
    }

    /**
     * @return int
     */
    public function getAuthorsLimit(): int
    {
        return $this->authorsLimit;
    }

    /**
     * @param int|null $authorsLimit
     *
     * @return void
     */
    public function setAuthorsLimit(?int $authorsLimit): void
    {
        $this->authorsLimit = max(0, $authorsLimit ?? BookRepository::AUTHORS_LIMIT);
    }
}
