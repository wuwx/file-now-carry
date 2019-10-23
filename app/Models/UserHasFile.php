<?php

namespace App\Models;

class UserHasFile extends Model
{
    /**
     * @var int
     */
    protected $userId;

    /**
     * @var string
     * @length 100
     */
    protected $fileName;

    /**
     * @var string
     * @length 50
     */
    protected $fileType;

    /**
     * @var integer
     */
    protected $fileSize;

    /**
     * @var int
     */
    protected $linksCount = 0;

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * @return int
     */
    public function getLinksCount(): int
    {
        return $this->linksCount;
    }
}