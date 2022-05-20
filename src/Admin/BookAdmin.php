<?php

namespace App\Admin;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\ORM\Query\Expr\Join;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Sonata\AdminBundle\FieldDescription\FieldDescriptionInterface;
use Sonata\AdminBundle\Filter\Model\FilterData;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Form\Type\ModelType;
use Sonata\AdminBundle\Show\ShowMapper;
use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\File;

/**
 * Class BookAdmin
 *
 * @package App\Admin
 */
class BookAdmin extends AbstractAdmin
{
    protected function configureFormFields(FormMapper $form): void
    {
        $book = $this->getSubject();
        $fileOptions = [
            'label' => 'Cover of book (PDF or image file)',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '1024k',
                    'mimeTypes' => [
                        'application/pdf',
                        'application/x-pdf',
                        'image/*',
                    ],
                    'mimeTypesMessage' => 'Please upload a valid PDF document',
                ]),
            ],
        ];
        if ($book && ($book->getCover())) {
            $request = $this->getRequest();
            $fullPath = $request->getBasePath() . $book->getCoverPath();

            $fileOptions['help'] = '<img src="' . $fullPath . '" class="admin-preview"/>';
            $fileOptions['help_html'] = true;
        }
        $authorsOptions = [
            'class' => Author::class,
            'property' => 'name',
            'multiple' => true,
        ];
        $form->add('title', TextType::class)
            ->add('description', TextareaType::class)
            ->add('year', NumberType::class)
            ->add('file', FileType::class, $fileOptions)
            ->add('authors', ModelType::class, $authorsOptions);
    }

    public function prePersist(object $object): void
    {
        $this->manageFileUpload($object);
    }

    public function preUpdate(object $object): void
    {
        $this->manageFileUpload($object);
    }

    private function manageFileUpload(object $book): void
    {
        if ($book->getFile()) {
            $book->refreshUpdated();
        }
    }

    protected function configureDatagridFilters(DatagridMapper $datagrid): void
    {
        $datagrid->add('title')
            ->add('description')
            ->add('year')
            ->add('authorsFilter', CallbackFilter::class, [
                'callback' => [$this, 'getAuthorsFilter'],
                'field_type' => EntityType::class,
                'field_options' => [
                    'class' => Author::class,
                    'choice_label' => 'name',
                ],
            ]);
    }

    public function getAuthorsFilter(ProxyQueryInterface $query, string $alias, string $field, FilterData $data): bool
    {
        if (!$data->hasValue()) {
            return false;
        }

        $author = $data->getValue();
        $query->join("$alias.authors", 'a', Join::WITH, 'a.id = :authorId')
            ->setParameter('authorId', $author->getId());

        return true;
    }

    protected function configureListFields(ListMapper $list): void
    {
        $list->addIdentifier('id')
            ->addIdentifier('title')
            ->addIdentifier('description')
            ->addIdentifier('year')
            ->add('authorsAll', null, [
                'label' => 'Authors',
                'accessor' => function (Book $subject) {
                    return $subject->getAuthorsAsString();
                },
            ])
            ->addIdentifier('cover');
    }

    protected function configureShowFields(ShowMapper $show): void
    {
        $show->add('id')
            ->add('title')
            ->add('description')
            ->add('year')
            ->add('img', FieldDescriptionInterface::TYPE_HTML, [
                'accessor' => function (Book $book) {
                    if ($book->getCover()) {
                        $request = $this->getRequest();
                        $fullPath = $request->getBasePath() . $book->getCoverPath();

                        return '<img src="' . $fullPath . '" class="admin-preview"/>';
                    }

                    return '';
                },
            ])
            ->add('authorsAll', null, [
                'label' => 'Authors',
                'accessor' => function (Book $subject) {
                    return $subject->getAuthorsAsString();
                },
            ]);
    }
}
