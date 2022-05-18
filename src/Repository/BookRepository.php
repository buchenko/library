<?php

namespace App\Repository;

use App\Dto\FilterBooks;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            $query->join('b.authors','a', Join::WITH, 'a.id = :authorId')
                ->setParameter('authorId', $filterBooks->getAuthor()->getId());
        }

        $query->orderBy('b.id', 'ASC')
            ->setMaxResults(10);

        return $query->getQuery()->getResult();
    }
}
