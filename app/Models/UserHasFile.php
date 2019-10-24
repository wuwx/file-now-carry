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
     * @length 32
     */
    protected $shareLink;

    /**
     * @var string
     * @length 100
     */
    protected $fileName;

    /**
     * @var string
     * @length 100
     */
    protected $fileType;

    /**
     * @var integer
     */
    protected $fileSize;

    /**
     * @var int
     */
    protected $linkUserId;

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
    public function getLinkUserId(): int
    {
        return $this->linkUserId;
    }

    /**
     * @return string
     */
    public function getShareLink(): string
    {
        return $this->shareLink;
    }

}