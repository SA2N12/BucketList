<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class FileUploader
{
    public function __construct(
        #[Autowire('%app.images_wish_directory%')]
        private readonly string $targetDirectory
    )
    {

    }
    public function upload(UploadedFile $file): string
    {
        $fileName = uniqid() . '.' . $file->guessExtension();
        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            // ... handle exception if something happens during file upload
        }
        return $fileName;
    }
    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }
    public function delete(?string $filename, string $rep): void
    {
        if (null != $filename) {
            if (file_exists($rep . '/' . $filename)) {
                unlink($rep . '/' . $filename);
            }
        }
    }
}
