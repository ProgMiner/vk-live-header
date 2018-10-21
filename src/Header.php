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
use VK\Client\VKApiClient;

/**
 * Class Header
 *
 * @package VkLiveHeader
 */
class Header implements \Serializable, \JsonSerializable {

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
     * @param VKApiClient $vk    VK client
     * @param VkGroup     $group Group
     *
     * @return array Response from photos.saveOwnerCoverPhoto VK API method
     *
     * @throws \VK\Exceptions\VKApiException
     * @throws \VK\Exceptions\VKClientException
     */
    public function refresh(VKApiClient $vk, VkGroup $group): array {
        $url = $vk->photos()->
            getOwnerCoverPhotoUploadServer($group->accessToken(), [
                'group_id' => $group->id()
            ])['upload_url'];

        $tmpfile = tmpfile();
        imagepng($this->sprite->render()->getResource(), $tmpfile);

        $meta = stream_get_meta_data($tmpfile);

        $photo = $vk->getRequest()->
            upload($url, 'photo', $meta['uri']);

        $resp = $vk->photos()->
            saveOwnerCoverPhoto($group->accessToken(), $photo);

        return $resp;
    }

    /**
     * @inheritdoc
     */
    public function serialize() {
        return serialize($this->sprite);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($serialized) {
        $this->sprite = unserialize($serialized);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return serialize($this->sprite);
    }
}