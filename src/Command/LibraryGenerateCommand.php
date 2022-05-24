<?php

namespace App\Command;

use App\Entity\Author;
use App\Entity\Book;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class LibraryGenerateCommand
 *
 * @package App\Command
 */
class LibraryGenerateCommand extends Command
{
    public const COUNT_AUTHORS = 10;
    public const COUNT_BOOKS = 15;
    public const MAX_COAUTHORS = 4;
    protected static $defaultName = 'library:generate';
    protected static $defaultDescription = 'generate demo books and authors';
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, string $name = null)
    {
        parent::__construct($name);
        $this->em = $em;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->setHelp('This command allows you to generate books (without covers) and authors for demo')
            ->addOption('authors', 'a',InputArgument::OPTIONAL, 'Count of authors', static::COUNT_AUTHORS)
            ->addOption('books', 'b',InputArgument::OPTIONAL, 'Count of books', static::COUNT_BOOKS)
            ->addOption('coauthors', 'c',InputArgument::OPTIONAL, 'Max count of coauthors for book', static::MAX_COAUTHORS)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $countAuthors = $input->getOption('authors');
        $countBooks = $input->getOption('books');
        $maxCoauthors = $input->getOption('coauthors');

        $authors = $this->generateAuthors($countAuthors);
        $output->writeln("generated $countAuthors authors");

        $this->generateBooks($countBooks, $authors, $maxCoauthors);
        $output->writeln("generated $countBooks books");

        $io->success('You generated data library for demo.');

        return 0;
    }

    /**
     * @param int $countAuthors
     *
     * @return array
     */
    private function generateAuthors(int $countAuthors): array
    {
        $authors = [];
        for ($index = 1; $index <= $countAuthors; $index++) {
            $author = new Author();
            $author->setName('Author ' . $index);
            $this->em->persist($author);
            $authors[] = $author;
        }
        $this->em->flush();

        return $authors;
    }

    /**
     * @param int $countBooks
     * @param array $authors
     * @param int $maxCoauthors
     *
     * @return array
     */
    private function generateBooks(int $countBooks, array $authors, int $maxCoauthors): array
    {
        $books = [];
        for ($index = 1; $index <= $countBooks; $index++) {
            $book = new Book();
            $book->setTitle('Book ' . $index);
            $book->setDescription('Description ' . $index);
            $book->setYear(rand(0,2022));
            $rand_keys = array_rand($authors, rand(1, $maxCoauthors));
            if (is_array($rand_keys)) {
                for ($indexAuthor = 0; $indexAuthor < count($rand_keys); $indexAuthor++) {
                    $book->addAuthor($authors[$rand_keys[$indexAuthor]]);
                }
            } else {
                $book->addAuthor($authors[$rand_keys]);
            }
            $this->em->persist($book);
            $books[] = $book;
        }
        $this->em->flush();

        return $books;
    }
}
