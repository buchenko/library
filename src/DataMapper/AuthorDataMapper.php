<?php

namespace App\DataMapper;

use App\Dto\Author as AuthorDto;
use App\Entity\Author;

/**
 * Class AuthorDataMapper
 *
 * @package App\DataMapper
 */
class AuthorDataMapper
{
    public function mapDtoToEntity(AuthorDto $dto, Author $entity)
    {
        if ($name = $dto->getName()) {
            $entity->setName($name);
        }
    }

    public function mapEntityToDto(Author $entity, AuthorDto $dto)
    {
        $dto->setName($entity->getName());
    }
}
