<?php

namespace Concierge\Commands;

use InstagramAPI\Push\Notification;
use Concierge\Commands\Job\TelegramSendText;
use InstagramAPI\Response\Model\DirectThread;
use InstagramAPI\Response\Model\DirectThreadItem;
use InstagramAPI\Instagram;

class HandlerPush
{

    private $instagram;
    private $push;

    public function __construct(string $client, Instagram $instagram, Notification $push)
    {
        $this->client = $client;
        $this->instagram = $instagram;
        $this->push = $push;        
    }

    public function parseDirectPush(){
        $push = $this->push;
        $client = $this->client;
        if ($push->getActionParam('id')) {
            /** @var DirectThread $thread */
            $thread = $this->getThread($push->getActionParam('id'));

            if ($push->getActionParam('x')) {
                $text = "[$client] @" . $thread->getUsers()[0]->getUsername() . ": ";

                foreach ($thread->getItems() as $item) {
                    if ($item->getItemId() == $push->getActionParam('x')) {
                        $text .= $this->handleItemType($item);
                    }
                }
            } else {
                $text = "[$client] **pending dm** @" . $thread->getUsers()[0]->getUsername() . ": ";
                $text .= $this->handleItemType($thread->getItems()[0]);
            }
            return new TelegramSendText($text, A_USER_CHAT_ID);
        }
    }

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
        $item = $item->getVisualMedia()->getMedia();
        switch ($item->getMediaType()) {
            case 1:
                $item = $item->getImageVersions2()['candidates'];
                break;
            case 2:
                $item = $item->getVideoVersions();
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
