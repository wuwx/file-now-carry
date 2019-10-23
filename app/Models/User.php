<?php

namespace App\Models;

class User extends Model
{
    /**
     * @var integer
     */
    protected $id;

    /**
     * @var string
     * @length 90
     */
    protected $shareLink;
}