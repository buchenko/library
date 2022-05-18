<?php

namespace App\ParamConverter;

use App\Dto\Book;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthorParamConverter
 *
 * @package App\ParamConverter
 */
class BookParamConverter implements ParamConverterInterface
{
    /**
     * @param Request $request
     * @param ParamConverter $configuration
     *
     * @return bool
     */
    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $class = $configuration->getClass();

        /** @var Book $class */
        $class = new $class();
        if ('title' === $request->request->get('field', '')) {
            $class->setTitle($request->request->get('value', ''));
        }
        if ('description' === $request->request->get('field', '')) {
            $class->setDescription($request->request->get('value', ''));
        }
        if ('year' === $request->request->get('field', '')) {
            $class->setYear($request->request->getInt('value'));
        }

        $request->attributes->set($configuration->getName(), $class);

        return true;
    }

    /**
     * @param ParamConverter $configuration
     *
     * @return bool
     */
    public function supports(ParamConverter $configuration): bool
    {
        if (null === $configuration->getClass()) {
            return false;
        }
        if ($configuration->getClass() === Book::class) {
            return true;
        }

        return is_subclass_of($configuration->getClass(), Book::class);
    }
}
