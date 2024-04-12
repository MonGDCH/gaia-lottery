<?php

declare(strict_types=1);

namespace plugins\lottery\dao;

use mon\log\Logger;
use mon\thinkOrm\Dao;
use mon\util\Instance;
use plugins\lottery\contract\GiftEnum;
use plugins\lottery\contract\RoundGiftEnum;

/**
 * 抽奖概率对应奖品Dao操作
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class ProbabilityGiftDao extends Dao
{
    use Instance;

    /**
     * 操作表
     *
     * @var string
     */
    protected $table = 'lottery_probability_gift';

    /**
     * 复制活动生效时对应的奖品信息
     *
     * @param integer $round_id 活动ID
     * @return boolean
     */
    public function copyRoundEffectGift(int $round_id): bool
    {
        Logger::instance()->channel()->info('Copy lottery round gift for probability');
        // 获取奖品信息
        $giftTable = GiftDao::instance()->getTable();
        $giftStatus = GiftEnum::GIFT_STATUS['enable'];
        $roundGiftTable = RoundGiftDao::instance()->getTable();
        $roundGiftStatus = RoundGiftEnum::GIFT_STATUS['enable'];
        $sql = "SELECT id, type, title, content, img FROM`{$giftTable}` WHERE `id` IN (SELECT `gift_id` FROM `{$roundGiftTable}` WHERE `round_id` = ? AND status = {$roundGiftStatus}) AND status = {$giftStatus}";
        $gifts = $this->query($sql, [$round_id]);
        if (!$gifts) {
            $this->error = '不存在有效活动奖品';
            return false;
        }
        // 保存复制奖品信息
        $data = [];
        $now = time();
        foreach ($gifts as $item) {
            $data[] = [
                'round_id'  => $round_id,
                'gift_id'   => $item['id'],
                'type'      => $item['type'],
                'title'     => $item['title'],
                'content'   => $item['content'],
                'img'       => $item['img'],
                'create_time' => $now
            ];
        }

        if (!empty($data)) {
            $save = $this->limit(1000)->insertAll($data);
            if (!$save) {
                $this->error = '保存抽奖奖品信息失败';
                return false;
            }
        }

        return true;
    }
}
