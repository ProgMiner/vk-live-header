#!/usr/bin/env php
<?php

/* MIT License

Copyright (c) 2018 Eridan Domoratskiy

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE. */

namespace ImageConstructor;

use Ds\Map;
use Symfony\Component\Config\Definition\Processor;
use VK\Client\VKApiClient;
use VK\Exceptions\VKApiException;
use VkLiveHeader\Configuration;
use VkLiveHeader\Header;
use VkLiveHeader\VkGroup;

if (file_exists(__DIR__.'/../../autoload.php')) {
    require __DIR__.'/../../autoload.php';
} else {
    require __DIR__.'/vendor/autoload.php';
}

(new class() {

    const HOME_DIR = __DIR__;

    const CONFIG_FILE = self::HOME_DIR.'/vk-live-header.json';
    const CACHE_FILE = self::HOME_DIR.'/vk-live-header.cache';

    const MAX_REQUESTS_PER_SEC = 3;

    /**
     * @var array Config array:
     *              - hash    - string - Config file hash
     *              - time    - int    - Last launch time
     *              - count   - int    - Count of launches in current second
     *              - headers - Map    - Groups with headers
     */
    private $config = [];

    public function __construct() {
        $this->loadConfig();
    }

    public function __invoke() {
        $vk = new VKApiClient();

        foreach ($this->config['headers'] as $group => $header) {
            $this->waitForLimit();

            try {
                /**
                 * @var VkGroup $group
                 * @var Header  $header
                 */
                $header->refresh($vk, $group);
            } catch (VKApiException $e) {
                fwrite(STDERR, $e->getMessage().PHP_EOL);
                fwrite(STDERR, $e->getTraceAsString().PHP_EOL);
            }
        }

        $this->saveConfig();
    }

    private function waitForLimit() {
        if (time() - $this->config['time'] > 0) {
            $this->config['time'] = time();
            $this->config['count'] = 1;
            return;
        }

        ++$this->config['count'];
        if ($this->config['count'] <= self::MAX_REQUESTS_PER_SEC) {
            usleep(1000000 / self::MAX_REQUESTS_PER_SEC);
            return;
        }

        echo 'sleep'.PHP_EOL;
        sleep(1);

        $this->waitForLimit();
    }

    private function loadConfig() {
        if (!file_exists(self::CONFIG_FILE)) {
            $this->config = [
                    'hash'    => 'd751713988987e9331980363e24189ce',
                    'time'    => time(),
                    'count'   => 0,
                    'headers' => new Map()
            ];

            return;
        }

        $configFile = file_get_contents(self::CONFIG_FILE);
        $hash = md5($configFile);

        while (file_exists(self::CACHE_FILE)) {
            $cache = unserialize(file_get_contents(self::CACHE_FILE));

            if ($cache['hash'] !== $hash) {
                break;
            }

            $this->config = $cache;
            return;
        }

        $processor = new Processor();

        $config = $processor->processConfiguration(
                new Configuration(),
                [json_decode($configFile, true)]
        );

        $headers = new Map();
        foreach ($config as $groupHeader) {
            $group = new VkGroup($groupHeader['group_id'], $groupHeader['access_token']);
            $header = unserialize(base64_decode($groupHeader['header']));

            $headers->put($group, $header);
        }

        $this->config = [
            'hash'    => $hash,
            'time'    => time(),
            'count'   => 0,
            'headers' => $headers
        ];
    }

    private function saveConfig() {
        file_put_contents(self::CACHE_FILE, serialize($this->config));
    }
})();