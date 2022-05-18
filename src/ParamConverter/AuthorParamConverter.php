<?php

namespace App\ParamConverter;

use App\Dto\Author;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AuthorParamConverter
 *
 * @package App\ParamConverter
 */
class AuthorParamConverter implements ParamConverterInterface
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

        /** @var Author $class */
        $class = new $class();
        if ('name' === $request->request->get('field', '')) {
            $class->setName($request->request->get('value', ''));
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
        if ($configuration->getClass() === Author::class) {
            return true;
        }

        return is_subclass_of($configuration->getClass(), Author::class);
    }
}
