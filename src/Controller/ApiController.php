<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
//use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ApiController extends AbstractController
{
    /**
     * @var array
     */
    private $articles;

    /**
     * @var string
     */
    private string $format = 'json';

    /**
     * @Route("/api/insert", name="api_insert", methods={"POST"})
     * @throws \Exception
     */
    public function insert(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON data provided'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setCreatedAt(new \DateTime($data['created_at']));
        $article->setPublishAt(new \DateTime($data['publish_at']));
        $article->setStatus($data['status']);

        $errors = $validator->validate($article);

        if (count($errors) > 0) {
            $errorMessages = [];

            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $errorMessages], JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($article);
        $entityManager->flush();

        return $this->json(['message' => 'Record created successfully'], JsonResponse::HTTP_CREATED);
    }


    /**
     * @Route("/api/show/all/{format?}", name="api_list_all_records", methods={"GET"})
     */
    public function listAllRecords(?string $format, ArticleRepository $articleRepository, SerializerInterface $serializer): Response
    {
        $this->articles = $articleRepository->findAll();

        return $this->formatResponse($format, $serializer);
    }

    /**
     * @Route("/api/show/active/{format?}", name="api_list_active_records", methods={"GET"})
     */
    public function listActiveRecords(?string $format, ArticleRepository $articleRepository, SerializerInterface $serializer): Response
    {
        $this->articles = $articleRepository->findActive();

        try {
            return $this->formatResponse($format, $serializer);
        } catch (Exception $e) {
            return $this->json(['error' => 'An error occurred while processing the request: ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @throws CannotInsertRecord
     * @throws Exception
     */
    public function formatResponse(?string $format = 'json', SerializerInterface $serializer): Response
    {
        $format = $this->setFormat($format);

        // TODO read from config
        $supportedFormats = ['json', 'xml', 'csv'];
        if (!in_array($format, $supportedFormats)) {
            return $this->json(['error' => 'Unsupported format provided. Supported formats are: json, xml, csv'], JsonResponse::HTTP_BAD_REQUEST);
        }

        switch ($format) {
            case 'json':
                $data = $serializer->serialize($this->articles, 'json', ['json_encode_options' => JSON_UNESCAPED_UNICODE]);
                $response = new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/json']);
                break;

            case 'xml':
                $data = $serializer->serialize($this->articles, 'xml');
                $response = new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
                break;

            case 'csv':
                $csv = Writer::createFromString('');
                $csv->insertOne(['id', 'name', 'date']); // Insert header row

                /** @var Article $article */
                foreach ($this->articles as $article) {
                    $csv->insertOne([
                        $article->getId(),
                        $article->getTitle(),
                        $article->getContent(),
                        $article->getCreatedAt()->format('Y-m-d H:i:s'),
                        $article->getPublishAt()->format('Y-m-d H:i:s'),
                        $article->getStatus(),
                    ]);
                }

                $response = new Response($csv->toString(), Response::HTTP_OK, ['Content-Type' => 'text/csv']);
                break;
        }

        return $response;
    }

    /**
     * @Route("/api/show/paginate/{format?}", name="api_list_all_records_paginate", methods={"GET"})
     */
    public function listAllRecordsPaginated(?string $format, ArticleRepository $articleRepository, SerializerInterface $serializer, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $articleRepository->createQueryBuilder('a')
            ->getQuery();

        $page = $request->query->getInt('page', 1); // current page or default to 1
        $limit = $request->query->getInt('limit', 10); // limit per page or default to 10

        $pagination = $paginator->paginate($query, $page, $limit);

        $this->articles = $pagination->getItems();

        $response = $this->formatResponse($format, $serializer);

        // Add pagination data to the response headers
        $response->headers->set('X-Pagination-Total-Count', $pagination->getTotalItemCount());
        $response->headers->set('X-Pagination-Page-Count', $pagination->getPageCount());
        $response->headers->set('X-Pagination-Current-Page', $pagination->getCurrentPageNumber());
        $response->headers->set('X-Pagination-Per-Page', $pagination->getItemNumberPerPage());

        return $response;
    }


    public function setFormat(?string $format): string
    {
        if (!is_null($format)) {
            $this->format = $format;
        }
        return $this->format;
    }
}