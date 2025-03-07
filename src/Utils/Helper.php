<?php

namespace Diatria\LaravelInstant\Utils;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

class Helper
{
    protected $value;

    /**
     * Menambahkan value user ID secara otomatis jika terdapat field `user_id` pada object ($fillable)
     * @param Illuminate\Support\Collection $fillable
     * @param Collection $request
     */
    static function appendUserID(Model $model, Collection $request)
    {
        if (!empty($request->get("user_id"))) {
            return $request;
        }
        if (Helper::hasUserID($model)) {
            return collect($request)->put("user_id", Helper::getUserID());
        }
        return $request;
    }

    /**
     * @param array $haystack
     * @param array $only
     */
    static function arrayOnly($haystack, $only): Collection
    {
        return collect($haystack)->map(fn($item) => collect($item)->only($only));
    }

    /**
     * Menghitung average dari array
     */
    static function avg(array $array): int
    {
        $sum = collect($array)->sum();
        return $sum / count($array);
    }

    /**
     * @param Integer Bytes
     */
    static function convertDiskCapacity($size)
    {
        $unit = ["b", "kb", "mb", "gb", "tb", "pb"];
        return @round($size / pow(1024, $i = floor(log($size, 1024))), 2) . " " . $unit[$i];
    }

    /**
     * formatting date to indonesia format
     * @param String $date
     */
    static function dateIndonesia($date): string|null
    {
        if (empty($date)) {
            return null;
        }
        return Carbon::parse($date, "Asia/Jakarta")->locale("id_ID")->isoFormat("D MMMM Y");
    }

    static function dateToday($format = "Y-m-d")
    {
        return Carbon::now()->format($format);
    }

    static function decimal($numb, $decimal = 2, $separator = ".")
    {
        return number_format($numb, $decimal, $separator, "");
    }

    /**
     * Menampilkan data dari nested array menggunakan "dot notation"
     */
    static function get($haystack, $query, $throw = null, $strict = false)
    {
        $th = new ThrowError($haystack, $query, $throw, $strict);
        return $th->result();
    }

    static function getDomain(string $domain = null, string $default = null, array $config = ["port" => true])
    {
        $http_origin = isset($_SERVER["HTTP_ORIGIN"]) ? $_SERVER["HTTP_ORIGIN"] : null;
        $http_referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : null;

        if (!$domain) {
            $domain = $http_origin ?? $http_referer;
        }
        if (!$domain) {
            $domain = $default;
        }

        if (!$domain) {
            throw new ErrorException("Tidak ada domain yang ditemukan", 500);
        }

        $parsedUrl = parse_url($domain);

        $url = $parsedUrl["host"];
        if ($config["port"] && isset($parsedUrl["port"])) {
            $url = $url . ":" . $parsedUrl["port"];
        }
        return $url;
    }

    static function getHost()
    {
        return $_SERVER["SERVER_NAME"];
    }

    static function getIP()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            //ip from share internet
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            //ip pass from proxy
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        if (env("APP_ENV") === "PRODUCTION") {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        return $ip;
    }

    static function getLang()
    {
        return $_COOKIE["lang"] ?? "en";
    }

    static function getModelName($model)
    {
        return class_basename(get_class($model));
    }

    static function getPermissionSlug($model, $action)
    {
        $modelClassName = class_basename(get_class($model));
        return "{$modelClassName}:{$action}";
    }

    static function getUserID()
    {
        if (config("laravel-instant.auth.driver", "sanctum") === "jwt") {
            $token = Token::info();
            return $token["user_id"] ?? null;
        } else {
            $user = auth("sanctum")->user();
            return $user ? $user->id : null;
        }
    }

    /**
     * @return Boolean
     */
    static function hasPermission(Request $request, $model, $action)
    {
        return true;
        $permissionSlug = self::getPermissionSlug($model, $action);
        if (!$request->user()->tokenCan($permissionSlug)) {
            throw new \Exception("Unauthorized", 401);
        }
    }

    static function hasUserID(Model $model): bool
    {
        return collect($model->getFillable())->contains("user_id");
    }

    static function log($message)
    {
        $message = is_array($message) ? json_encode($message) : $message;
        Log::debug($message);
    }

    static function minuteToTime(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $hoursLeadingZero = $hours < 10 ? "0" . $hours : $hours;

        $minutes = $minutes % 60;
        $minutesLeadingZero = $minutes < 10 ? "0" . $minutes : $minutes;

        return $hoursLeadingZero . ":" . $minutesLeadingZero . ":00";
    }

    static function throw($message): \Exception
    {
        throw new \Exception($message);
    }

    static function th($haystack, $query, $throw = null, $strict = false)
    {
        $th = new ThrowError($haystack, $query, $throw, $strict);
        return $th->result();
    }

    static function toObject($haystack, $flag = false)
    {
        $json = json_encode($haystack, $flag);
        return json_decode($json);
    }

    static function toArray($haystack, $arrayAssociative = true)
    {
        return json_decode(json_encode($haystack), $arrayAssociative);
    }

    static function toArrayCollection($haystack, $flag = true): Collection
    {
        return collect(self::toArray($haystack, $flag));
    }
}
