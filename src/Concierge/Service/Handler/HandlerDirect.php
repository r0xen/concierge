<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Push\Notification;
use InstagramAPI\Response\Model\DirectThread;
use InstagramAPI\Response\Model\DirectThreadItem;
use InstagramAPI\Instagram;
use Concierge\Models\Direct;

class HandlerDirect implements HandlerInterface
{

    /**
     * Parses a direct push notification
     *
     * @return Direct
     */
    public static function parsePush(string $client, Instagram $ig, Notification $push): Direct
    {
        var_dump($push);
        if ($push->getActionParam('id')) {
            /** @var DirectThread $thread */
            $thread = self::getThread($ig, $push->getActionParam('id'));
            $from = $thread->getUsers()[0]->getUsername();

            if ($push->getActionParam('t') === 'p') { // pending request
                $text = self::handleItemType($thread->getItems()[0]);
                return new Direct($from, $client, $text, $thread->getItems()[0]->getItemType(), true);
            }

            if ($push->getActionParam('x')) {
                foreach ($thread->getItems() as $item) {
                    if ($item->getItemId() == $push->getActionParam('x')) {
                        $text = self::handleItemType($item);
                        return new Direct($from, $client, $text, $item->getItemType());
                    }
                }
            } else {
                return new Direct($from, $client, $push->getMessage(), 'text');
            }
        }
    }

    /**
     * Helper function switch to right handler according to item type
     *
     * @param DirectThreadItem $item
     * @return string
     */
    public static function handleItemType(DirectThreadItem $item): string
    {
        switch ($item->getItemType()) {
            case 'text':
                return self::handleText($item);
            case 'raven_media':
                return self::handleRavenMedia($item);
            case 'media':
                return self::handleMedia($item);
            case 'voice_media':
                return self::handleAudio($item);
            case 'reel_share':
                return self::handleReelShare($item); // story reply
            default:
                return '';
        }
    }

    /**
     * Helper function 
     *
     * @param string $id
     * @return DirectThread
     */
    private static function getThread(Instragram $instagram, string $id): DirectThread
    {
        return $instagram->direct->getThread($id)->getThread();
    }

    /**
     * Handle RavenMedia items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private static function handleRavenMedia(DirectThreadItem $item): string
    {

        $item = $item->getVisualMedia()['media'];

        switch ($item['media_type']) {
            case 1:
                $item = $item['image_versions2']['candidates'];
                break;
            case 2:
                $item = $item['video_versions'];
                break;
        }
        return $item[0]['url'];
    }

    /**
     * Handle Media items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private static function handleMedia(DirectThreadItem $item): string
    {
        if ($item->getMedia()->isVideoVersions()) {
            $item = $item->getMedia()->getVideoVersions();
        } else {
            $item = $item->getMedia()->getImageVersions2()->getCandidates();
        }
        return $item[0]->getUrl();
    }

    /**
     * Handle Audio items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private static function handleAudio(DirectThreadItem $item): string
    {
        return $item->getVoiceMedia()['media']['audio']['audio_src'];
    }

    /**
     * HandleReelShare
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private static function handleReelShare(DirectThreadItem $item): string
    {
        return $item->getReelShare()->getText();
    }

    /**
     * Handle text items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private static function handleText(DirectThreadItem $item): string
    {
        return $item->getText();
    }
}
