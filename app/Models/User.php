<?php


namespace App\Models;

class User extends Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     * @length 32
     */
    protected $shareLink;

    /**
     * @var int
     */
    protected $isShare = 1;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function getIsShare(): int
    {
        return $this->isShare;
    }

    /**
     * @return string
     */
    public function getShareLink(): string
    {
        return $this->shareLink;
    }
}