<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 25/05/2018
 * Time: 15:35
 */
namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

class Page extends Eloquent
{
    protected $connection = 'mongodb';

    protected $collection = 'page';

    public $timestamps = false;

    protected $hidden = ['_id'];
}
