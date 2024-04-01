<?php

namespace App\Controller;

use Aws\S3\S3Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class UploadController extends AbstractController
{
    #[Route('/upload', name: 'app_upload')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('GET')) {
            return $this->render('upload/index.html.twig');
        }

        $file = $request->files->get('file');
        if (!$file || $file->getClientMimeType() !== 'application/pdf') {
            return new Response('Invalid file type.', Response::HTTP_BAD_REQUEST);
        }

        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => $_ENV['AWS_S3_REGION'],
            'credentials' => [
                'key'    => $_ENV['AWS_S3_KEY'],
                'secret' => $_ENV['AWS_S3_SECRET'],
            ],
            'endpoint' => $_ENV['AWS_S3_ENDPOINT'],
            'use_path_style_endpoint' => true,
        ]);

        $bucketName = $_ENV['AWS_S3_BUCKET'];
        $filename = uniqid().'.'.$file->guessExtension();

        try {
            $result = $s3->putObject([
                'Bucket' => $bucketName,
                'Key'    => "uploads/{$filename}",
                'SourceFile' => $file->getRealPath(),
                'ACL'    => 'public-read',
            ]);

            return new Response($result->get('ObjectURL'), Response::HTTP_OK);
        } catch (\Exception $e) {
            return new Response('An error occurred: '.$e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
