<?php

namespace App\Controller;

use App\Entity\Article;
use App\Repository\ArticleRepository;
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
     * @Route("/api/insert", name="api_insert", methods={"POST"})
     */
    public function insert(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setCreatedAt(new \DateTime($data['created_at']));
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
     * @Route("/api/show/{format}", name="api_list_records", methods={"GET"}, requirements={"format"="json|xml|csv"})
     * @throws Exception
     */
    public function listRecords(string $format, ArticleRepository $articleRepository, SerializerInterface $serializer): Response
    {
        $articles = $articleRepository->findAll();

        switch ($format) {
            case 'json':
                $data = $serializer->serialize($articles, 'json');
                $response = new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/json']);
                break;

            case 'xml':
                $data = $serializer->serialize($articles, 'xml');
                $response = new Response($data, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
                break;

            case 'csv':
                $csv = Writer::createFromString('');
                $csv->insertOne(['id', 'name', 'date']); // Insert header row

                foreach ($articles as $article) {
                    $csv->insertOne([
                        $article->getId(),
                        $article->getName(),
                        $article->getDate()->format('Y-m-d'),
                    ]);
                }

                $response = new Response($csv->toString(), Response::HTTP_OK, ['Content-Type' => 'text/csv']);
                break;
        }

        return $response;
    }
}