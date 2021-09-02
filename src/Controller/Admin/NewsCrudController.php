<?php

namespace App\Controller\Admin;

use App\Entity\News;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class NewsCrudController extends AbstractCrudController
{
    private $logger;

    public static function getEntityFqcn(): string
    {
        return News::class;
    }

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function configureActions(Actions $actions): Actions
    {

        $actions->add(
            Crud::PAGE_INDEX,
            Action::new('update', 'Update', 'fa fa-download')
                ->linkToCrudAction('updateNews')
                ->createAsGlobalAction()
        );

        return parent::configureActions($actions); // TODO: Change the autogenerated stub
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }

    public function updateNews(AdminContext $context)
    {
        $client = HttpClient::create();

        $response = $client->request('GET', 'http://static.feed.rbc.ru/rbc/logical/footer/news.rss');

        if ($response->getStatusCode() == '200') {
            $crawler = new Crawler();

            $crawler->addXmlContent($response->getContent());

            $news = $crawler->filterXPath('//rss/channel/item')->each(function ($domElement) {
                if ($domElement->filter('link')->count()) {
                    $link = $domElement->filter('link')->text();
                }

                if ($domElement->filter('title')->count()) {
                    $title = $domElement->filter('title')->text();
                }

                $description = '';
                if ($domElement->filter('description')->count()) {
                    $description = $domElement->filter('description')->text();
                }

                $author = '';
                if ($domElement->filter('author')->count()) {
                    $author = $domElement->filter('author')->text();
                }

                if ($domElement->filter('pubDate')->count()) {
                    $pubDate = new \DateTime($domElement->filter('pubDate')->text());
                } else {
                    $pubDate = new \DateTime('now');
                }

                $image = '';
                if ($domElement->filter('enclosure')->count()) {
                    $image = $domElement->filter('enclosure')->attr('url');
                }
                return compact('link','title','description', 'author', 'pubDate', 'image');
            });

            $em = $this->getDoctrine()->getManager();

            foreach ($news as $item) {
                $element = $this->getDoctrine()->getManager()->getRepository(News::class)->findBy(['link' => $item['link']]);

                if (!$element) {
                    $news = new News;
                    $news->setTitle($item['title']);
                    $news->setDescription($item['description']);
                    $news->setLink($item['link']);
                    $news->setAuthor($item['author']);
                    $news->setPubDate($item['pubDate']);
                    $news->setImage($item['image']);

                    $em->persist($news);
                    $em->flush($news);
                }
            }
        }

        $this->addFlash('success', 'News updated!');

        return $this->redirect($context->getReferrer());
    }
}
