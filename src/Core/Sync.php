<?php
/**
 * Created by PhpStorm.
 * User: HanSon
 * Date: 2017/1/14
 * Time: 11:21
 */

namespace Hanson\Vbot\Core;


use Hanson\Vbot\Support\Console;

class Sync
{

    /**
     * get a message code
     *
     * @return array
     */
    public function checkSync()
    {
        $url = 'https://webpush.' . server()->domain . '/cgi-bin/mmwebwx-bin/synccheck?' . http_build_query([
                'r' => time(),
                'sid' => server()->sid,
                'uin' => server()->uin,
                'skey' => server()->skey,
                'deviceid' => server()->deviceId,
                'synckey' => server()->syncKeyStr,
                '_' => time()
            ]);

        $content = http()->get($url, [], ['timeout' => 35]);

        try{
            preg_match('/window.synccheck=\{retcode:"(\d+)",selector:"(\d+)"\}/', $content, $matches);

            return [$matches[1], $matches[2]];
        }catch (\Exception $e){
            Console::log('Sync check return:' . $content, Console::ERROR);
            return [-1, -1];
        }
    }

    public function sync()
    {
        $url = sprintf(server()->baseUri . '/webwxsync?sid=%s&skey=%s&lang=zh_CN&pass_ticket=%s', server()->sid, server()->skey, server()->passTicket);

        try{
            $result = http()->json($url, [
                'BaseRequest' => server()->baseRequest,
                'SyncKey' => server()->syncKey,
                'rr' => ~time()
            ], true);

            if($result['BaseResponse']['Ret'] == 0){
                $this->generateSyncKey($result);
            }

            return $result;
        }catch (\Exception $e){
            $this->sync();
        }
    }

    /**
     * generate a sync key
     *
     * @param $result
     */
    public function generateSyncKey($result)
    {
        server()->syncKey = $result['SyncKey'];

        $syncKey = [];

        if(is_array(server()->syncKey['List'])){
            foreach (server()->syncKey['List'] as $item) {
                $syncKey[] = $item['Key'] . '_' . $item['Val'];
            }
        }

        server()->syncKeyStr = implode('|', $syncKey);
    }
}