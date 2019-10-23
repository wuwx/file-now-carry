<?php

namespace App\Models;

class Admin extends Model
{
    /**
     * @var string
     * @length 50
     */
    protected $username;

    /**
     * @var string
     * @length 90
     */
    protected $password;

    /**
     * Admin constructor.
     * @param string $username
     * @param string $password
     */
    public function __construct(string $username = null, string $password = null)
    {
        $this->username = $username;
        $this->password = $password;
    }


    /**
     * @return string
     * @length 50
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }
}