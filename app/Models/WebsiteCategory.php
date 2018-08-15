<?php

namespace App\Models;

use Jenssegers\Mongodb\Eloquent\Model as Eloquent;

/**
 * Class WebsiteCategory
 *
 * @property string shortHeading
 * @property string heading
 * @property string parentCode
 * @property string cmsContent
 * @property string enabledFacets
 * @property string sortOrder
 * @property string matcher
 * @property array  classifications
 * @property string metaTitle
 * @property string htmlHeading
 * @property string metaDescription
 *
 * @package App\Models
 */
class WebsiteCategory extends Eloquent
{
    protected $connection = 'catalogue';

    protected $collection = 'websiteCategories';

    public $timestamps = false;

    protected $hidden = ['_id'];

    protected $primaryKey = '_id';

    public function parent()
    {
        return $this->belongsTo(WebsiteCategory::class, 'parentCode');
    }

    public function children()
    {
        return $this->hasMany(WebsiteCategory::class, 'parentCode');
    }
}
