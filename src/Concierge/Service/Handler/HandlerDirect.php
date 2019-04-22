<?php declare (strict_types = 1);

namespace Concierge\Service\Handler;

use InstagramAPI\Push\Notification;
use Concierge\Commands\Job\TelegramSendText;
use InstagramAPI\Response\Model\DirectThread;
use InstagramAPI\Response\Model\DirectThreadItem;
use InstagramAPI\Instagram;
use Concierge\Models\Direct;
use Concierge\Commands\CommandInterface;

class HandlerDirect implements HandlerInterface
{
    /**
     * Instance to instagram service
     *
     * @var Instagram
     */
    private $instagram;
    /**
     * Push notif
     *
     * @var Push
     */
    private $push;

    /**
     * Constructor
     *
     * @param string $client
     * @param Instagram $instagram
     * @param Notification $push
     */
    public function __construct(string $client, Instagram $instagram, Notification $push)
    {
        $this->client = $client;
        $this->instagram = $instagram;
        $this->push = $push;
    }
    /**
     * Parses a direct push notification
     *
     * @return Direct
     */
    private function parsePush(): Direct
    {
        $push = $this->push;
        $client = $this->client;
        if ($push->getActionParam('id')) {
            /** @var DirectThread $thread */
            $thread = $this->getThread($push->getActionParam('id'));
            $from = $thread->getUsers()[0]->getUsername();

            if ($push->getActionParam('x')) {
                foreach ($thread->getItems() as $item) {
                    if ($item->getItemId() == $push->getActionParam('x')) {
                        $text = $this->handleItemType($item);
                        return new Direct($from, $client, $text, $item->getItemType());
                    }
                }
            }
            $text = $this->handleItemType($thread->getItems()[0]);

            return new Direct($from, $client, $text, $thread->getItems()[0]->getItemType(), true);
        }
    }

    /**
     * Returns the Job to do
     *
     * @return CommandInterface
     */
    public function retrieveCommand(): CommandInterface
    {
        $direct = $this->parsePush();
        if ($direct->isPending()) {
            $text = sprintf("<i>[%s] pending dm</i> @%s", $direct->getClient(), $direct->getFrom());
        }
        $text = sprintf("<i>[%s]</i> @%s", $direct->getClient(), $direct->getFrom());

        if ($direct->getType() !== "text" && $direct->getType() !== "reel_share") {
            $text .= sprintf(' sent you a <a href="%s">media</a>', $direct->getText());
            return new TelegramSendText($text, A_USER_CHAT_ID);
        }

        $text .= ": " . $direct->getText();
        return new TelegramSendText($text, A_USER_CHAT_ID);
    }
    /**
     * Helper function switch to right handler according to item type
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleItemType(DirectThreadItem $item): string
    {
        switch ($item->getItemType()) {
            case 'text':
                return $this->handleText($item);
            case 'raven_media':
                return $this->handleRavenMedia($item);
            case 'media':
                return $this->handleMedia($item);
            case 'voice_media':
                return $this->handleAudio($item);
            case 'reel_share':
                return $this->handleReelShare($item); // story reply
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
    private function getThread(string $id): DirectThread
    {
        return $this->instagram->direct->getThread($id)->getThread();
    }

    /**
     * Handle RavenMedia items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleRavenMedia(DirectThreadItem $item): string
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
    private function handleMedia(DirectThreadItem $item): string
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
    private function handleAudio(DirectThreadItem $item): string
    {
        return $item->getVoiceMedia()['media']['audio']['audio_src'];
    }

    /**
     * HandleReelShare
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleReelShare(DirectThreadItem $item): string
    {
        return $item->getReelShare()->getText();
    }

    /**
     * Handle text items
     *
     * @param DirectThreadItem $item
     * @return string
     */
    private function handleText(DirectThreadItem $item): string
    {
        return $item->getText();
    }
}
