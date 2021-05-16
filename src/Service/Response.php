<?php
namespace App\Service;

use App\Entity\File;
use Kzu\Security\Crypto;
use App\Service\FileSystemApi;
use Mediashare\ShowContent\ShowContent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

Class Response {
    public function json(array $response, int $status = 200): JsonResponse {
        $response = new JsonResponse($response, $status);
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    public function render(ShowContent $content): SymfonyResponse {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Headers: *");
        return new SymfonyResponse($content->show());
    }

    public function show(File $file): BinaryFileResponse {
        if ($file->getEncrypt()):
            $content = Crypto::decrypt(file_get_contents($file->getPath()), $file->getApikey());
            $filesystem = new FileSystemApi();
            $filesystem->write($filepath = tempnam("", "tmp"), $content);
        else: $filepath = $file->getPath(); endif;

        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $file->getName()
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        return $response;
    }

    public function download(File $file): BinaryFileResponse {
        if ($file->getEncrypt()):
            $content = Crypto::decrypt(file_get_contents($file->getPath()), $file->getApikey());
            $filesystem = new FileSystemApi();
            $filesystem->write($filepath = tempnam("", "tmp"), $content);
        else: $filepath = $file->getPath(); endif;

        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getName()
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Headers', '*');
        $response->headers->set('Content-Length', $file->getSize());
        return $response;
    }
}