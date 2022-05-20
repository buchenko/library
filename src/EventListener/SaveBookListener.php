<?php

namespace App\EventListener;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

/**
 * Class SaveBookListener
 *
 * @package App\EventListener
 */
class SaveBookListener
{
    /** @var Author[] */
    private array $inserted = [];
    /** @var Author[] */
    private array $removed = [];
    private string $tableName = 'authors';

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     * @throws Exception
     */
    public function prePersist(Book $book, LifecycleEventArgs $event)
    {
        $this->inserted = $book->getAuthors()->toArray();
        $em = $event->getEntityManager();
        $this->updateAuthors($em);
    }

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     */
    public function postPersist(Book $book, LifecycleEventArgs $event)
    {
        $this->inserted = $book->getAuthors()->toArray();
        $em = $event->getEntityManager();
        foreach ($this->inserted as $author) {
            $countBooks = $author->getCountBooks();
            $author->setCountBooks(++$countBooks);
            $em->persist($author);
        }
        $this->inserted = [];
    }

    /**
     * @param Book $book
     * @param PreUpdateEventArgs $event
     *
     * @return void
     */
    public function preUpdate(Book $book, PreUpdateEventArgs $event)
    {
        $collection = $book->getAuthors();
        if ($collection->isDirty()) {
            $this->inserted = $collection->getInsertDiff();
            $this->removed = $collection->getDeleteDiff();
        }
    }

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     * @throws Exception
     */
    public function postUpdate(Book $book, LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $this->updateAuthors($em);
    }

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     */
    public function preRemove(Book $book, LifecycleEventArgs $event)
    {
        $this->removed = $book->getAuthors()->toArray();
    }

    /**
     * @throws Exception
     */
    public function postRemove(Book $book, LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $this->updateAuthors($em);
    }

    /**
     * @param EntityManagerInterface $em
     *
     * @return void
     * @throws Exception
     */
    protected function updateAuthors(EntityManagerInterface $em)
    {
        $connection = $em->getConnection();

        $inserted = array_map(function (Author $author) {
            return $author->getId();
        }, $this->inserted);
        if ($inserted) {
            $this->doUpdate($inserted, $connection);
            $this->inserted = [];
        }

        $removed = array_map(function (Author $author) {
            return $author->getId();
        }, $this->removed);
        if ($removed) {
            $this->doUpdate($removed, $connection, false);
            $this->removed = [];
        }
    }

    /**
     * @param array $authorIds
     * @param Connection $connection
     * @param bool $increase
     *
     * @return void
     * @throws Exception
     */
    protected function doUpdate(array $authorIds, Connection $connection, bool $increase = true)
    {
        $action = $increase ? "+" : "-";
        $sqlUpdate = "UPDATE {$this->tableName} SET count_books = (count_books $action 1) WHERE id IN (:authorIds);";
        $values = ['authorIds' => $authorIds];
        $types = ['authorIds' => Connection::PARAM_INT_ARRAY];
        $connection->executeStatement($sqlUpdate, $values, $types);
    }
}
