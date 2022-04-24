<?php

namespace App\classes;

use App\Models\Error;
use App\Models\ApiXyList;
use App\Models\ApiTwitter;
use Illuminate\Support\Facades\Http;

class ApiExtra
{
    protected static $rate_limit_twitter = 40; // max 450 calls per 15 minute Twitter

    /**
     * Get Twitter details
     * @param string $usernames Comma separated usernames
     * @return mixed 
     */
    public static function getTwittersFromApi(string $usernames)
    {
        $url = 'https://api.twitter.com/2/users/by';
        return Http::withToken(config('app.twitter_token'))->acceptJson()->get($url, [
            'usernames' => $usernames, // max 100
            'user.fields' => 'public_metrics,created_at,url',
        ])->object();
    }

    /**
     * Update Twitter stats for max 30 days old coins in DB
     * @return void 
     */
    public static function updateTwitterStats()
    {
        $created_at = now();
        $timestamp = time();
        ApiXyList::toBase()->select('twitter')
            ->where('updated_at', '>=', now()->subDays(30))
            ->where('twitter', '!=', '')
            ->orderBy('twitter')
            ->groupBy('twitter')
            ->chunk(100, function ($chunks) use ($timestamp, $created_at) {
                $usernames_100 = $chunks->pluck('twitter')
                    ->filter(function ($v, $key) {
                        $pattern = '/^[A-Za-z0-9_]{1,15}$/'; // Twitter filter
                        return preg_match($pattern, $v);
                    })
                    ->toArray();
                $usernames_100_str = implode(',', $usernames_100);
                $twitter_response = self::getTwittersFromApi($usernames_100_str);
                self::apiSleep('twitter');
                if (!isset($twitter_response->data)) {
                    $e = new Error;
                    $msg = $twitter_response->errors[0]->message ?? '';
                    $e->msg = $msg;
                    $e->params = ($msg == '') ? json_encode($twitter_response) : '';
                    $e->save();
                    return;
                }

                $grouped = [];
                $twitter_response = $twitter_response->data;
                foreach ($twitter_response as $v) {
                    try {
                        $twitter_id = $v->id;
                        $name = $v->username;
                        $followers = $v->public_metrics->followers_count;
                        $tweets = $v->public_metrics->tweet_count;
                        $listed = $v->public_metrics->listed_count;
                        $grouped[] = compact('twitter_id', 'name', 'followers', 'tweets', 'listed', 'timestamp', 'created_at');
                    } catch (\Throwable $th) {
                        $e = new Error;
                        $e->msg = 'foreach ($twitter_response as $v) ' . $th->getMessage();
                        $e->params = json_encode($v) . json_encode($th);
                        $e->save();
                    }
                }
                if ($grouped) {
                    try {
                        ApiTwitter::insert($grouped);
                    } catch (\Throwable $th) {
                        $e = new Error;
                        $e->msg = 'ApiTwitter::insert ' . $th->getMessage();
                        $e->params = json_encode($th);
                        $e->save();
                    }
                }
            });
    }

    /**
     * Prevent to reach API rate limit calls with usleep() with -15% calls 
     * @param string $api twitter
     * @return void 
     */
    public static function apiSleep(string $api = 'cmc')
    {
        $max_call_per_minute = match ($api) {
            'twitter' => self::$rate_limit_twitter,
            default => 30
        };

        usleep(60 / $max_call_per_minute * 1.1 * 1000000); // Sleep in microseconds, +15% sleep time    
    }

}
