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

namespace VkLiveHeader;

use ImageConstructor\Sprite;
use ImageConstructor\Utility;
use VK\Client\VKApiClient;

/**
 * Class Header
 *
 * @package VkLiveHeader
 */
class Header {

    /**
     * @var Sprite Sprite that will be used as header
     */
    public $sprite;

    public function __construct(Sprite $sprite) {
        $this->sprite = $sprite;
    }

    /**
     * Uploads rendered $sprite to VK as header of group
     *
     * @param VKApiClient $vk VK client
     * @param int $groupId Group ID
     * @param string $accessToken Access token
     *
     * @return array Response from photos.saveOwnerCoverPhoto VK API method
     *
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function refresh(VKApiClient $vk, int $groupId, string $accessToken): array {
        $url = $vk->photos()->
            getOwnerCoverPhotoUploadServer($accessToken, [
                'group_id' => $groupId
            ])['upload_url'];

        $tmpfile = tmpfile();
        imagepng(Utility::imageToGD($this->sprite->render()), $tmpfile);

        $meta = stream_get_meta_data($tmpfile);

        $photo = $vk->getRequest()->
            upload($url, 'photo', $meta['uri']);

        $resp = $vk->photos()->
            saveOwnerCoverPhoto($accessToken, $photo);

        return $resp;
    }
}