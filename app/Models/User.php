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
     * @length 90
     */
    protected $shareLink;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getShareLink(): string
    {
        return $this->shareLink;
    }


}