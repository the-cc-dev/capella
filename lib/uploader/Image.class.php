<?php
require_once('../AWS/Storage.php');

class WrongFileSize extends Exception {}

class WrongFileType extends Exception {}

class AccessDenied extends Exception {}

class Uploader {    #Абстрактный класс, в котором описываются допустимые расширения и максимальный размер, а так же методы проверки.

    const MAX_FILE_SIZE = 15*1024*1024*8; #15 МБ
    const EXTENSIONS = array(   #Допустимые MIME-типы
        'image/jpg',
        'image/png',
        'image/jpeg',
        'image/gif'
    );

    protected $fileSize;
    protected $fileExt;
    protected $filePath;

    protected function checkExtension() #Проверка расширения
    {
        if (!in_array($this->fileExt, self::EXTENSIONS)) {
            throw new WrongFileType("Wrong file type.");
        }
    }

    protected function checkSize()  #Проверка размера
    {
        if ($this->fileSize > self::MAX_FILE_SIZE) {
            throw new WrongFileSize('The file is too big.');
        }
    }
}

/**
 * Класс по работе с файлом.
 */
class FileUploader extends Uploader
{
    public function __construct($img)
    {
        if (!is_uploaded_file($img['tmp_name'])) {
            throw new AccessDenied("Access denied. File wasn't uploaded");
        }

        if (isset($img['type'])) {
            $this->fileExt = mime_content_type($img['tmp_name']);
        }
        if (isset($_FILES['ImageFile']['size'])) {
            $this->fileSize = $img['size'];
        }
        if (isset($_FILES['ImageFile']['tmp_name'])) {
            $this->filePath = $img['tmp_name'];
        }
    }

    public function upload()
    {
        $this->checkExtension();    #Проверяем
        $this->checkSize();

        $storage = new AWS_Storage();   #Загружаем на облако
        $imgID = $storage->uploadImage($this->filePath);
        $imgURI = $storage->getImage($imgID);
        return $imgURI;
    }
}

/**
 * Класс по работе с ссылкой на файл.
 */
class LinkUploader extends Uploader
{
    private $fileName;

    public function __construct($url)
    {
        $this->fileName = basename($url['ImageLink']);
        $this->fileExt = mime_content_type($url);
        $this->fileSize = get_headers($url['ImageLink']);
        $this->fileSize = $this->fileSize['Content-Length'];
    }

    public function upload()
    {
        $this->checkExtension(); #Проверяем
        $this->checkSize();

        file_put_contents($this->fileName, file_get_contents($_GET['ImageLink'])); #Сохраняем
        $this->filePath = realpath($this->fileName);

        $storage = new AWS_Storage();   #Загружаем на облако
        $imgID = $storage->uploadImage($this->filePath);
        $imgURI = $storage->getImage($imgID);

        unlink($this->filePath);    #Удаляем
        return $imgURI;
    }
}