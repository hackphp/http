<?php

namespace HackPHP\Http\Parsers;

use HackPHP\Http\Stream\StreamFactory;
use HackPHP\Http\Upload\UploadedFileFactory;

class UploadedFilesParser
{
    public function __invoke(array $files)
    {
        return $this->parse($files);
    }

    private function parse(array $files)
    {
        $parsed = [];

        foreach ($files as $field => $uploadedFile) {
            // Parse multiple files field
            if (!isset($uploadedFile['error'])) {
                if (is_array($uploadedFile)) {
                    $parsed[$field] = $this->parse($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];

            if (!is_array($uploadedFile['error'])) {
                $parsed[$field] = $this->createUploadedFile($uploadedFile);
            } else {
                $subArray = [];

                $k = array_keys($uploadedFile['error']);

                foreach ($k as $singleK) {
                    $subArray[$singleK]['name'] = $uploadedFile['name'][$singleK];
                    $subArray[$singleK]['type'] = $uploadedFile['type'][$singleK];
                    $subArray[$singleK]['tmp_name'] = $uploadedFile['tmp_name'][$singleK];
                    $subArray[$singleK]['error'] = $uploadedFile['error'][$singleK];
                    $subArray[$singleK]['size'] = $uploadedFile['size'][$singleK];
                    $parsed[$field] = $this->parse($subArray);
                }
            }
        }

        return $parsed;
    }

    private function createUploadedFile($uploadedFile)
    {
        $factory = new UploadedFileFactory;

        return $factory->createUploadedFile(
            (new StreamFactory)->createStreamFromFile($uploadedFile['tmp_name'], "r+"),
            $uploadedFile['size'] ?? null,
            $uploadedFile['error'],
            $uploadedFile['name'] ?? null,
            $uploadedFile['type'] ?? null
        );
    }
}
