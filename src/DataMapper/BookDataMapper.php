<?php

namespace App\DataMapper;

use App\Dto\Book as BookDto;
use App\Entity\Book;

/**
 * Class AuthorDataMapper
 *
 * @package App\DataMapper
 */
class BookDataMapper
{
    public function mapDtoToEntity(BookDto $dto, Book $entity)
    {
        if ($title = $dto->getTitle()) {
            $entity->setTitle($title);
        }
        if ($description = $dto->getDescription()) {
            $entity->setDescription($description);
        }
        if ($year = $dto->getYear()) {
            $entity->setYear($year);
        }
    }

    public function mapEntityToDto(Book $entity, BookDto $dto)
    {
        $dto->setTitle($entity->getTitle());
        $dto->setDescription($entity->getDescription());
        $dto->setYear($entity->getYear());
    }
}
