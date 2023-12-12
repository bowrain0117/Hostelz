<?php

namespace App\Booking;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/*
    We may later have a SavedRoomType database table that will serialize this class and store it for some systems along with things like
    the relevant imported ID, etc.  Then connected to that can be things like photo URLs that are stored in our system.
*/

class RoomInfo
{
    use HasFactory;
    // Values of null means the value is unknown

    public $code;

    public $name;

    public $description;

    public $type; // private/dorm

    public $ensuite; // true/false/null (unknown)

    public $sex; // (male/female/mixed/null)

    public $photoURLs;

    public $roomSurface;    //  room_surface_in_m2 from booking.com

    public $bedsPerRoom; // required for private rooms, optional for dorms. not displayed to the user, just used for matching room types between systems.

    public $peoplePerRoom; // required for private rooms, optional for dorms.

    public const TYPE_DORM = 'dorm';

    public const TYPE_PRIVATE = 'private';

    /* (i don't think we need this here)
    public $minNights, $maxNights; // these may also be overrided by the properties of RoomAvailability
    */

    public function __construct($properties = [])
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    public function peoplePerBookableBlock(): int
    {
        return $this->type === self::TYPE_DORM ? 1 : $this->peoplePerRoom;
    }

    public function isSimilar(self $other): bool
    {
        return
            $this->isSimilarForCompare($other) &&
            $this->ensuite === $other->ensuite;
    }

    public function isSimilarForCompare(self $other): bool
    {
        // (we make 'mixed' equivalent to undefined for comparison purposes)
        $thisSex = ($this->sex === 'mixed' ? null : $this->sex);
        $otherSex = ($other->sex === 'mixed' ? null : $other->sex);

        $similarRoomSurface = true;
        if (isset($this->roomSurface, $other->roomSurface)) {
            $similarRoomSurface = $this->roomSurface === $other->roomSurface;
        }

        return
            $thisSex === $otherSex &&
            $this->type === $other->type &&
            $this->bedsPerRoom === $other->bedsPerRoom &&
            $this->peoplePerRoom === $other->peoplePerRoom &&
            $similarRoomSurface;
    }

    public function isSimilarForMergeCheck(self $other): bool
    {
        $thisSex = $this->sex === 'mixed' ? null : $this->sex;
        $otherSex = $other->sex === 'mixed' ? null : $other->sex;

        return
            $thisSex === $otherSex &&
            $this->type === $other->type &&
            $this->bedsPerRoom === $other->bedsPerRoom &&
            $this->peoplePerRoom === $other->peoplePerRoom;
    }

    /* Return false if there are errors or missing information.  Also logs a warning if there is. */

    public function isValid(): bool
    {
        $expectedProperties = ['code', 'name', 'description', 'type', 'ensuite', 'sex', 'photoURLs', 'bedsPerRoom', 'peoplePerRoom', 'roomSurface'];
        if (! arraysHaveEquivalentValues(array_keys(get_object_vars($this)), $expectedProperties)) {
            return $this->logValidationWarning('Has unknown properties added: ' . implode(', ', array_diff(array_keys(get_object_vars($this)), $expectedProperties)));
        }
        if ($this->code === '') {
            return $this->logValidationWarning('Missing code.');
        }
        if ($this->name === '') {
            return $this->logValidationWarning('Missing name.');
        }
        // Make the room name capitalized if it was all lowercase
        if ($this->name === mb_strtolower($this->name)) {
            $this->name = mb_convert_case($this->name, MB_CASE_TITLE, 'UTF-8');
        }
        if (! in_array($this->type, [self::TYPE_DORM, self::TYPE_PRIVATE])) {
            return $this->logValidationWarning("Unknown type '$this->type'.");
        }
        if ($this->ensuite !== null && $this->ensuite !== true && $this->ensuite !== false) {
            return $this->logValidationWarning("Unknown ensuite '$this->ensuite'.");
        }
        if (! empty($this->sex) && ! in_array($this->sex, ['male', 'female', 'mixed'])) {
            return $this->logValidationWarning("Unknown sex '$this->sex'.");
        }

        if ($this->type === self::TYPE_DORM) {
            if ($this->peoplePerRoom !== $this->bedsPerRoom) {
                return $this->logValidationWarning("peoplePerRoom ($this->peoplePerRoom) != $this->bedsPerRoom ($this->bedsPerRoom) for dorm.");
            }
        } else { // private room
            if (! $this->bedsPerRoom) {
                return $this->logValidationWarning('bedsPerRoom not set.');
            }
            if ($this->bedsPerRoom > $this->peoplePerRoom) {
                return $this->logValidationWarning("bedsPerRoom ($this->bedsPerRoom) > peoplePerRoom ($this->peoplePerRoom)");
            }
        }

        return true;
    }

    private function logValidationWarning($text): bool
    {
        logWarning("Validation error for $this->code: $text");

        return false; // used as the return value of isValid()
    }

    public function getDebugInfo(): string
    {
        return
            "[code: $this->code, type: $this->type, ensuite: " . $this->outputTrueFalseNull($this->ensuite) .
            ", sex: $this->sex, bedsPerRoom: $this->bedsPerRoom, peoplePerRoom: $this->peoplePerRoom]";
    }

    private function outputTrueFalseNull($v): string
    {
        if ($v === null) {
            return 'null';
        }
        if ($v === false) {
            return 'false';
        }
        if ($v === true) {
            return 'true';
        }

        throw new Exception("Unknown value '$v'.");
    }

    public function newCollection(array $models = [])
    {
        return collect($models);
    }
}
