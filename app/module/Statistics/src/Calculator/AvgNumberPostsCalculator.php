<?php

declare(strict_types = 1);

namespace Statistics\Calculator;

use DateTime;
use SocialPost\Dto\SocialPostTo;
use Statistics\Dto\StatisticsTo;

class AvgNumberPostsCalculator extends AbstractCalculator
{
    protected const UNITS = 'posts';

    /**
     * @var array
     */
    private $postsPerUser = [];

    /**
     * @var array
     */
    private $postsCreatedRangePerUsers = [];

    /**
     * @var array
     */
    private $averagePostsPerUserPerMonth = [];

    /**
     * Accumulates the posts per user and the range of those 
     * posts for calculating the average posts
     * 
     * @param SocialPostTo $postTo
     * @return void
     */
    protected function doAccumulate(SocialPostTo $postTo): void
    {
        $authorId = $postTo->getAuthorId();
        $this->postsPerUser[$authorId] = ($this->postsPerUser[$authorId] ?? 0) + 1;

        if (!array_key_exists($authorId, $this->postsCreatedRangePerUsers)) {
            $postsCreatedRangeForUser = new class() {
                public $earliestPostDateTime;
                public $latestPostDateTime;
            };
            $postsCreatedRangeForUser->earliestPostDateTime = $postTo->getDate();
            $postsCreatedRangeForUser->latestPostDateTime = $postTo->getDate();
            $this->postsCreatedRangePerUsers[$authorId] = $postsCreatedRangeForUser;
        }

        $postsCreatedRangeForUser = $this->postsCreatedRangePerUsers[$authorId];
        if ($postsCreatedRangeForUser->earliestPostDateTime > $postTo->getDate()) {
            $postsCreatedRangeForUser->earliestPostDateTime = $postTo->getDate();
        }
        if ($postsCreatedRangeForUser->latestPostDateTime < $postTo->getDate()) {
            $postsCreatedRangeForUser->latestPostDateTime = $postTo->getDate();
        }
        $this->postsCreatedRangePerUsers[$authorId] = $postsCreatedRangeForUser;
    }

    /**
     * @inheritDoc
     */
    protected function doCalculate(): StatisticsTo
    {
        // calculate the average posts per month per for each user
        foreach ($this->postsPerUser as $authorId => $totalPosts) {
            $postsCreatedRangeForUser = $this->postsCreatedRangePerUsers[$authorId];
            $totalMonths = $this->getMonthsBetween($postsCreatedRangeForUser->earliestPostDateTime, $postsCreatedRangeForUser->latestPostDateTime);
            $this->averagePostsPerUserPerMonth[$authorId] = round(($totalPosts / $totalMonths), 2);
        }

        // collect stats
        $stats = new StatisticsTo();
        foreach ($this->averagePostsPerUserPerMonth as $authorId => $avg) {
            $child = (new StatisticsTo())
                ->setName($this->parameters->getStatName())
                ->setSplitPeriod($authorId)
                ->setValue($avg)
                ->setUnits(self::UNITS);

            $stats->addChild($child);
        }

        return $stats;
    }

    /**
     * Calculates the months between two dates but taking into account only the months of the dates. 
     * Returns at least 1 for a range that is less than a month
     * 
     * @param DateTime $start
     * @param DateTime $end
     * @return int
     */
    private function getMonthsBetween(DateTime $start, DateTime $end): int
    {
        $start->modify('first day of this month');
        $end->modify('last day of this month');
        $interval = $start->diff($end);
        $monthsDiff = $interval->m;
        $yearsDiff = $interval->y;
        $yearsDiffInMonths = $yearsDiff * 12;
        $totalMonths = $yearsDiffInMonths + $monthsDiff + 1;
        return $totalMonths;
    }
}
