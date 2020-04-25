<?php

namespace Weirdo\Entrust\Models;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 * @license MIT
 * @package Weirdo\Entrust
 */
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Weirdo\Entrust\Traits\EntrustModuleTrait;
use Weirdo\Entrust\Contracts\EntrustModuleInterface;

class EntrustModule extends Model implements EntrustModuleInterface
{
    use EntrustModuleTrait;

    /**
     * The database table used by the model.
     * @var string
     */
    protected $table;

    /**
     * Creates a new instance of the model.
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = Config::get('entrust.modules_table');
    }
}
