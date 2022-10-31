<?php

namespace App\Models;

use App\Classes\Pterodactyl;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    public $incrementing = false;

    public $guarded = [];

    public static function boot()
    {
        parent::boot(); // TODO: Change the autogenerated stub

        static::deleting(function (Location $location) {
            $location->nodes()->each(function (Node $node) {
                $node->delete();
            });
        });
    }

    /**
     * Sync locations with pterodactyl panel
     * @throws Exception
     */
    public static function syncLocations()
    {
        $locations = Pterodactyl::getLocations();

        //map response
        $locations = array_map(function ($val) {
            return array(
                'id'          => $val['attributes']['id'],
                'name'        => $val['attributes']['short'],
                'description' => $val['attributes']['long']
            );
        }, $locations);

        //update or create
        foreach ($locations as $location) {
            self::query()->updateOrCreate(
                [
                    'id' => $location['id']
                ],
                [
                    'name'        => $location['name'],
                    'description' => $location['name'],
                ]
            );
        }

        self::removeDeletedLocation($locations);
    }

    /**
     * @description remove locations that have been deleted on pterodactyl
     * @param array $locations
     */
    private static function removeDeletedLocation(array $locations): void
    {
        $ids = array_map(function ($data) {
            return $data['id'];
        }, $locations);

        self::all()->each(function (Location $location) use ($ids) {
            if (!in_array($location->id, $ids)) $location->delete();
        });
    }

    public function nodes()
    {
        return $this->hasMany(Node::class, 'location_id', 'id');
    }

}
