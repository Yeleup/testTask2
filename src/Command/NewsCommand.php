<?php

namespace App\Command;

use App\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class NewsCommand extends Command
{
    protected static $defaultName = 'app:news';
    protected static $defaultDescription = 'Add a short description for your command';
    protected $entityManager;
    protected $logger;

    public function __construct(string $name = null, EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $em = $this->entityManager;

        $client = HttpClient::create();

        $response = $client->request('GET', 'http://static.feed.rbc.ru/rbc/logical/footer/news.rss');

        $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();

        $crawler = new Crawler();
        $crawler->addXmlContent($response->getContent());

        $news = $crawler->filterXPath('//rss/channel/item');

        foreach ($news as $item) {
            $domElement = new Crawler($item);

            $news = new News;

            if ($domElement->filter('link')->count()) {
                $element = $this->entityManager->getRepository(News::class)->findBy(['link' => $domElement->filter('link')->text()]);
            } else {
                continue;
            }

            if (!$element) {
                try {
                    if ($domElement->filter('title')->count()) {
                        $news->setTitle($domElement->filter('title')->text());
                    }

                    if ($domElement->filter('title')->count()) {
                        $news->setDescription($domElement->filter('description')->text());
                    }

                    if ($domElement->filter('link')->count()) {
                        $news->setLink($domElement->filter('link')->text());
                    }


                    if ($domElement->filter('author')->count()) {
                        $news->setAuthor($domElement->filter('author')->text());
                    }

                    if ($domElement->filter('pubDate')->count()) {
                        $pubDate = new \DateTime($domElement->filter('pubDate')->text());
                        $news->setPubDate($pubDate);
                    } else {
                        $pubDate = new \DateTime('now');
                        $news->setPubDate($pubDate);
                    }

                    if ($domElement->filter('enclosure')->count()) {
                        $news->setImage($domElement->filter('enclosure')->attr('url'));
                    }

                    $em->persist($news);
                    $em->flush($news);
                } catch (\Exception $e) {
                    $this->logger->critical('News updated cancel', [
                        'requestedMethod' => $request->getMethod(),
                        'requestUrl' => $request->getRequestUri(),
                        'responseCode' => $e->getCode(),
                        'responseBody' => $e->getMessage(),
                    ]);
                }
            }
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        $this->logger->info('News updated', [
            'requestedMethod' => $request->getMethod(),
            'requestUrl' => $request->getRequestUri(),
            'responseCode' => $response->getStatusCode(),
            'responseBody' => $response->getContent(),
        ]);

        return Command::SUCCESS;
    }
}
