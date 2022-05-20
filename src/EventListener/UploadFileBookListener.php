<?php

namespace App\EventListener;

use App\Entity\Book;
use App\Service\FileUploader;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * Class UploadListener
 *
 * @package App\EventListener
 */
class UploadFileBookListener
{
    private FileUploader $fileUploader;

    public function __construct(FileUploader $fileUploader)
    {
        $this->fileUploader = $fileUploader;
    }

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     */
    public function prePersist(Book $book, LifecycleEventArgs $event)
    {
        $this->manageFileUpload($book);
    }

    /**
     * @param Book $book
     * @param LifecycleEventArgs $event
     *
     * @return void
     */
    public function preUpdate(Book $book, LifecycleEventArgs $event)
    {
        $this->manageFileUpload($book);
    }

    /**
     * @param Book $book
     *
     * @return void
     */
    private function manageFileUpload(Book $book): void
    {
        if ($file = $book->getFile()) {
            $fileName = $this->fileUploader->upload($file);
            $book->setCover($fileName);
            $book->setFile(null);
        }
    }
}
